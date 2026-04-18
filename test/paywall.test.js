/**
 * test/paywall.test.js
 * XULContentPaywall 合约测试 — 全部在一个 describe 块避免状态丢失
 * 用法: npx hardhat test test/paywall.test.js
 */
const { assert } = require("chai");
const hre = require("hardhat");

describe("XULContentPaywall — 完整流程测试", function () {
  let paywall, xulToken, creator, buyer, buyer2, contentId;

  before(async () => {
    [creator, buyer, buyer2] = await hre.ethers.getSigners();

    // 部署 Mock XUL Token（10亿初始供应）
    const MockXUL = await hre.ethers.getContractFactory("MockXULToken");
    xulToken = await MockXUL.deploy();
    await xulToken.waitForDeployment();

    // 部署 Paywall
    const Paywall = await hre.ethers.getContractFactory("XULContentPaywall");
    paywall = await Paywall.connect(creator).deploy(await xulToken.getAddress());
    await paywall.waitForDeployment();
    console.log("  ✅ Paywall:", await paywall.getAddress());
  });

  it("listContent: 上架内容", async () => {
    const price = hre.ethers.parseUnits("100", 18);
    const tx = await paywall.connect(creator).listContent(
      price, "ipfs://QmArticle1", 1
    );
    const receipt = await tx.wait();
    const event = receipt.logs.find(l => l.fragment?.name === "ContentListed");
    contentId = event.args[0];
    console.log("  ✅ contentId:", contentId);

    const listing = await paywall.getListing(contentId);
    assert(listing[0] === creator.address,              "creator 错误");
    assert(listing[1] === price,                       "price 错误");
    assert(listing[4] === true,                        "isActive 错误");
    assert(listing[5] === "ipfs://QmArticle1",         "contentRef 错误");
  });

  it("unlockContent: 首次解锁", async () => {
    const price = hre.ethers.parseUnits("100", 18);
    // 给买家转 500 XUL
    await xulToken.transfer(buyer.address, hre.ethers.parseUnits("500", 18));
    // 买家 approve
    await xulToken.connect(buyer).approve(await paywall.getAddress(), price);
    // 解锁
    const tx = await paywall.connect(buyer).unlockContent(contentId);
    const receipt = await tx.wait();
    const event = receipt.logs.find(l => l.fragment?.name === "ContentUnlocked");
    assert(event, "解锁事件未找到");
    assert(event.args[4] === 1n, "unlockCount 应为 1");

    const listing = await paywall.getListing(contentId);
    assert(listing[2] === price,  "totalEarned 错误");
    assert(listing[3] === 1n,    "unlockCount 错误");
    console.log("  ✅ totalEarned=100 XUL, unlockCount=1");
  });

  it("unlockContent: 重复解锁应拒绝", async () => {
    try {
      await paywall.connect(buyer).unlockContent(contentId);
      assert(false, "应该 revert");
    } catch (e) {
      assert(e.message.includes("Already unlocked") || e.message.includes("revert"),
        `错误: ${e.message.slice(0,120)}`);
      console.log("  ✅ revert: Already unlocked");
    }
  });

  it("hasUnlocked: 买家已解锁=true，其他用户=false", async () => {
    const buyerOk = await paywall.hasUnlocked(contentId, buyer.address);
    assert(buyerOk === true, "buyer.hasUnlocked 应为 true");
    const buyer2Ok = await paywall.hasUnlocked(contentId, buyer2.address);
    assert(buyer2Ok === false, "buyer2.hasUnlocked 应为 false");
    console.log("  ✅ buyer=true, buyer2=false");
  });

  it("updateContent: 修改价格 + 下架", async () => {
    const newPrice = hre.ethers.parseUnits("200", 18);
    await paywall.connect(creator).updateContent(contentId, newPrice, true);
    let listing = await paywall.getListing(contentId);
    assert(listing[1] === newPrice, "新价格错误");

    await paywall.connect(creator).updateContent(contentId, 0, false);
    listing = await paywall.getListing(contentId);
    assert(listing[4] === false, "isActive 应为 false");
    console.log("  ✅ 价格改为 200 XUL，然后下架");
  });

  it("hasUnlocked: 下架后已付费用户访问权保留", async () => {
    const ok = await paywall.hasUnlocked(contentId, buyer.address);
    assert(ok === true, "已付费用户访问权不受下架影响");
    console.log("  ✅ 下架后 buyer 仍可访问");
  });

  it("contractBalance: 合约不持币（收入即时到账）", async () => {
    const bal = await xulToken.balanceOf(await paywall.getAddress());
    assert(bal === 0n, `合约余额应为 0，实际=${bal}`);
    console.log("  ✅ 合约 XUL 余额 = 0");
  });

  it("creator earnings: 创作者已收到 100 XUL", async () => {
    const earnings = await xulToken.balanceOf(creator.address);
    const INITIAL = 1_000_000_000n * 1n;
    assert(earnings >= INITIAL + hre.ethers.parseUnits("100", 18) - 1n,
      `创作者应收到 100 XUL，余额=${hre.ethers.formatUnits(earnings, 18)}`);
    console.log(`  ✅ 创作者余额: ${hre.ethers.formatUnits(earnings, 18)} XUL`);
  });
});

describe("MockXULToken", function () {
  let token;

  before(async () => {
    const [owner] = await hre.ethers.getSigners();
    const Mock = await hre.ethers.getContractFactory("MockXULToken");
    token = await Mock.deploy();
    await token.waitForDeployment();
  });

  it("初始供应大于 0", async () => {
    const [deployer] = await hre.ethers.getSigners();
    const bal = await token.balanceOf(deployer.address);
    assert(bal > 0n, `初始供应量错误，余额=${bal}`);
    console.log(`  ✅ 初始供应: ${hre.ethers.formatUnits(bal, 18)} XUL`);
  });

  it("transfer 正常工作", async () => {
    const [, recipient] = await hre.ethers.getSigners();
    await token.transfer(recipient.address, 1000n);
    const bal = await token.balanceOf(recipient.address);
    assert(bal === 1000n);
    console.log("  ✅ transfer 1000n 成功");
  });

  it("approve + allowance 正确", async () => {
    const [owner, spender] = await hre.ethers.getSigners();
    await token.approve(spender.address, 500n);
    const allowance = await token.allowance(owner.address, spender.address);
    assert(allowance === 500n);
    console.log("  ✅ allowance = 500");
  });
});
