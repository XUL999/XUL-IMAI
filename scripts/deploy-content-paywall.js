/**
 * deploy-content-paywall.js
 * 部署 XULContentPaywall 到 XUL Chain（含自动重试）
 *
 * 用法: node scripts/deploy-content-paywall.js
 */
require("dotenv").config();
const hre = require("hardhat");

const XUL_TOKEN = "0x2222E9644CD033AeD841434f149f787c32c3aB54";
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
  const signers = await hre.ethers.getSigners();
  const creator = signers[0];
  console.log("创作者:", creator.address);

  // ── 部署合约 ─────────────────────────────────────────────
  console.log("\n正在部署 XULContentPaywall...");
  const Contract = await hre.ethers.getContractFactory("XULContentPaywall");
  const raw = await Contract.connect(creator).deploy(XUL_TOKEN);
  const contract = await withRetry(() => raw.waitForDeployment(), "等待部署确认");
  const address = await contract.getAddress();
  console.log("✅ XULContentPaywall 已部署到:", address);

  // ── 验证 XUL_TOKEN ─────────────────────────────────────
  const tokenAddr = await withRetry(() => contract.XUL_TOKEN(), "查 XUL_TOKEN");
  console.log("✅ XUL_TOKEN:", tokenAddr, tokenAddr === XUL_TOKEN ? "✅" : "❌");

  // ── 测试1：上架内容 ────────────────────────────────────
  const price = hre.ethers.parseUnits("100", 18);
  console.log("\n[测试1] 上架内容，价格:", hre.ethers.formatUnits(price, 18), "XUL");
  const tx1 = await withRetry(
    () => contract.connect(creator).listContent(price, "ipfs://QmMyArticle123", 1),
    "上架交易"
  );
  const receipt1 = await withRetry(() => tx1.wait(), "上架确认");
  const event1 = receipt1.logs.find(log => log.fragment?.name === "ContentListed");
  const contentId = event1?.args?.[0];
  console.log("✅ contentId:", contentId);

  // ── 测试2：查询 listing ────────────────────────────────
  const listing = await withRetry(() => contract.getListing(contentId), "查询 listing");
  console.log("\n[测试2] Listing 验证:");
  console.log("  创作者:", listing[0] === creator.address ? "✅" : "❌");
  console.log("  价格:", Number(listing[1]) === Number(price) ? "✅" : "❌");
  console.log("  累计收入:", listing[2].toString() === "0" ? "✅" : "❌");
  console.log("  解锁次数:", listing[3].toString() === "0" ? "✅" : "❌");
  console.log("  上架状态:", listing[4] === true ? "✅" : "❌");

  // ── 测试3：hasUnlocked(false) ─────────────────────────
  const beforeUnlock = await withRetry(
    () => contract.hasUnlocked(contentId, creator.address),
    "hasUnlocked(买前)"
  );
  console.log("\n[测试3] hasUnlocked(创作者自检):", beforeUnlock === false ? "✅ 未解锁" : "❌");

  // ── 测试4：上架第二篇内容（价格不同）──────────────────
  console.log("\n[测试4] 上架第二篇内容...");
  const price2 = hre.ethers.parseUnits("50", 18);
  const tx4 = await withRetry(
    () => contract.connect(creator).listContent(price2, "ipfs://QmArticle2", 2),
    "第二篇上架"
  );
  const receipt4 = await withRetry(() => tx4.wait(), "第二篇确认");
  const event4 = receipt4.logs.find(log => log.fragment?.name === "ContentListed");
  const contentId2 = event4?.args?.[0];
  console.log("  ✅ contentId2:", contentId2);
  console.log("  ✅ 两篇内容 ID 不同:", contentId !== contentId2 ? "✅" : "❌");

  // ── 测试5：更新价格 ───────────────────────────────────
  console.log("\n[测试5] 更新第一篇价格...");
  const newPrice = hre.ethers.parseUnits("200", 18);
  const tx5 = await withRetry(
    () => contract.connect(creator).updateContent(contentId, newPrice, true),
    "更新价格"
  );
  await withRetry(() => tx5.wait(), "更新确认");
  const listingNew = await withRetry(() => contract.getListing(contentId), "查询新 listing");
  console.log("  新价格:", Number(listingNew[1]) === Number(newPrice) ? "✅ 200 XUL" : "❌");

  // ── 测试6：下架内容 ───────────────────────────────────
  console.log("\n[测试6] 下架第一篇内容...");
  const tx6 = await withRetry(
    () => contract.connect(creator).updateContent(contentId, 0, false),
    "下架交易"
  );
  await withRetry(() => tx6.wait(), "下架确认");
  const listingOff = await withRetry(() => contract.getListing(contentId), "查询下架后 listing");
  console.log("  isActive:", listingOff[4] === false ? "✅ 已下架" : "❌");

  // ── 测试7：第二篇内容上架中（不受第一篇影响）─────────
  const listing2 = await withRetry(() => contract.getListing(contentId2), "查询第二篇状态");
  console.log("\n[测试7] 第二篇内容不受下架影响:", listing2[4] === true ? "✅ 仍在架" : "❌");

  // ── 测试8：getListing 非存在内容应 revert ───────────
  console.log("\n[测试8] 查询未上架内容应 revert...");
  const fakeId = "0x0000000000000000000000000000000000000000000000000000000000000001";
  try {
    await contract.getListing(fakeId);
    console.log("  ❌ 应该 revert!");
  } catch (e) {
    console.log("  ✅ revert: Not listed");
  }

  console.log("\n═══════════════════════════════════════");
  console.log("  合约地址:", address);
  console.log("  浏览器:", `https://scan.rswl.ai/address/${address}`);
  console.log("  XUL Token:", XUL_TOKEN);
  console.log("═══════════════════════════════════════");
}

main().catch(e => {
  console.error("\n❌ 部署失败:", e.message || e);
  process.exit(1);
});
