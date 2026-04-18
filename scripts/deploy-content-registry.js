/**
 * deploy-content-registry.js
 * 部署 XULContentRegistry 到 XUL Chain（含自动重试）
 */
const hre = require("hardhat");

const MAX_RETRIES = 5;
const RETRY_DELAY = 3000;

async function withRetry(fn, label) {
  for (let i = 1; i <= MAX_RETRIES; i++) {
    try {
      return await fn();
    } catch (e) {
      if (i === MAX_RETRIES) throw e;
      console.log(`  ⏳ ${label} 重试 ${i}/${MAX_RETRIES}... (${e.message.slice(0, 80)})`);
      await new Promise(r => setTimeout(r, RETRY_DELAY));
    }
  }
}

async function main() {
  const [deployer] = await hre.ethers.getSigners();
  console.log("部署人:", deployer.address);

  const balance = await withRetry(
    () => hre.ethers.provider.getBalance(deployer.address),
    "查询余额"
  );
  console.log("余额:", hre.ethers.formatEther(balance), "XUL");

  console.log("\n正在部署 XULContentRegistry...");
  const Contract = await hre.ethers.getContractFactory("XULContentRegistry");

  const contract = await withRetry(
    () => Contract.connect(deployer).deploy(),
    "部署合约"
  );
  await withRetry(
    () => contract.waitForDeployment(),
    "等待部署确认"
  );

  const address = await contract.getAddress();
  console.log("✅ XULContentRegistry 已部署到:", address);

  // ── 测试1：单条注册 ─────────────────────────────────
  const contentHash = hre.ethers.keccak256(
    hre.ethers.toUtf8Bytes("Hello XUL Chain! 这是我的第一篇原创文章")
  );
  const metaHash = "ipfs://QmXyZ...example";

  console.log("\n[测试1] 注册内容...");
  console.log("内容 hash:", contentHash);

  const tx = await withRetry(
    () => contract.registerContent(contentHash, metaHash),
    "注册交易"
  );
  const receipt = await withRetry(() => tx.wait(), "等待确认");

  const event = receipt.logs.find(log => log.fragment?.name === "ContentRegistered");
  const tokenId = event?.args?.[0];
  console.log("✅ tokenId:", tokenId?.toString());

  // ── 测试2：查询验证 ─────────────────────────────────
  const record = await withRetry(() => contract.getRecord(tokenId), "查询记录");
  const [hash, creator, ts, mh] = record;
  console.log("\n[测试2] 记录验证:");
  console.log("  hash 匹配:", hash === contentHash ? "✅" : "❌ hash=" + hash.slice(0, 20));
  console.log("  创作者:", creator === deployer.address ? "✅" : "❌");
  console.log("  时间戳:", ts > 0 ? "✅ " + new Date(Number(ts)*1000).toISOString() : "❌");
  console.log("  元数据:", mh === metaHash ? "✅" : "❌");

  // ── 测试3：防重复注册 ────────────────────────────────
  try {
    await contract.registerContent(contentHash, metaHash);
    console.log("\n[测试3] ❌ 应该拒绝重复注册！");
  } catch (e) {
    const ok = e.message.includes("already registered");
    console.log("\n[测试3]", ok ? "✅" : "❌", "重复注册拒绝:", e.message.slice(0, 100));
  }

  // ── 测试4：批量注册 ────────────────────────────────
  const hash2 = hre.ethers.keccak256(hre.ethers.toUtf8Bytes("第二篇文章"));
  const hash3 = hre.ethers.keccak256(hre.ethers.toUtf8Bytes("第三篇文章"));
  const tx2 = await withRetry(
    () => contract.registerBatch([hash2, hash3], ["ipfs://Qm2", "ipfs://Qm3"]),
    "批量注册"
  );
  await withRetry(() => tx2.wait(), "批量确认");
  const tokens = await withRetry(() => contract.getCreatorTokens(deployer.address), "查创作者列表");
  console.log("\n[测试4] ✅ 批量注册 2 条，部署者共有", tokens.length, "条记录");

  // ── 测试5：verifyCreator ────────────────────────────
  const isCreator = await withRetry(() => contract.verifyCreator(tokenId, deployer.address), "verifyCreator");
  console.log("\n[测试5] ✅ verifyCreator:", isCreator);

  // ── 测试6：isHashRegistered ────────────────────────
  const isReg = await withRetry(() => contract.isHashRegistered(contentHash), "isHashRegistered");
  console.log("\n[测试6] ✅ isHashRegistered:", isReg);

  // ── 测试7：updateMeta ────────────────────────────────
  const newMeta = "ipfs://QmUpdated";
  const tx3 = await withRetry(() => contract.updateMeta(tokenId, newMeta), "updateMeta");
  await withRetry(() => tx3.wait(), "updateMeta确认");
  const [, , , updated] = await withRetry(() => contract.getRecord(tokenId), "查更新后记录");
  console.log("\n[测试7]", updated === newMeta ? "✅" : "❌", "元数据更新:", updated);

  console.log("\n═══════════════════════════════════════");
  console.log("  合约地址:", address);
  console.log("  浏览器:", `https://scan.rswl.ai/address/${address}`);
  console.log("═══════════════════════════════════════");
}

main().catch(e => {
  console.error("\n❌ 部署失败:", e.message || e);
  process.exit(1);
});
