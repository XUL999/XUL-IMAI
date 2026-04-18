// SPDX-License-Identifier: MIT
pragma solidity ^0.8.28;

/**
 * @title XULContentPaywall
 * @notice 内容付费解锁合约 — 创作即变现
 *
 * 机制：
 * 1. 创作者为内容设置价格（XUL ERC-20 token）
 * 2. 用户 approve + 调用 unlockContent 解锁
 * 3. 收入即时转给创作者，无中间商
 * 4. 已付费用户永久免再次付费
 *
 * 适用于：文章/素材/模板/提示词/课程/数字人形象等数字内容变现
 */
contract XULContentPaywall {
    // ── 数据结构 ──────────────────────────────────────────

    struct ContentListing {
        address  creator;         // 创作者
        uint256  priceInXUL;      // 解锁价格（最小单位，1 XUL = 1e18）
        uint256  totalEarned;     // 累计收入
        uint256  unlockCount;     // 已解锁次数
        bool     isActive;        // 是否上架
        string   contentRef;      // 内容引用（IPFS CID / 加密内容指针）
        uint256  registryTokenId; // 可选：关联 XULContentRegistry tokenId
    }

    // ── 状态变量 ──────────────────────────────────────────

    /// @dev XUL ERC-20 token 地址
    address public immutable XUL_TOKEN;

    /// @dev contentId → ContentListing
    mapping(bytes32 => ContentListing) public listings;

    /// @dev contentId + 用户 → 是否已解锁
    mapping(bytes32 => mapping(address => bool)) public unlocked;

    /// @dev 全局计数器
    uint256 public nextContentId = 1;

    // ── Events ─────────────────────────────────────────────

    event ContentListed(
        bytes32 indexed contentId,
        address indexed creator,
        uint256 priceInXUL,
        string contentRef,
        uint256 registryTokenId
    );

    event ContentUnlocked(
        bytes32 indexed contentId,
        address indexed buyer,
        address indexed creator,
        uint256 amountXUL,
        uint256 unlockCount
    );

    event ContentUpdated(
        bytes32 indexed contentId,
        uint256 newPrice,
        bool isActive
    );

    // ── 构造函数 ───────────────────────────────────────────

    constructor(address _xulToken) {
        require(_xulToken != address(0), "XUL token address required");
        XUL_TOKEN = _xulToken;
    }

    // ── 核心函数 ───────────────────────────────────────────

    /**
     * @notice 上架内容（设置价格）
     * @param _priceInXUL     解锁价格，单位最小单位
     * @param _contentRef     内容引用字符串
     * @param _registryTokenId 可选：关联 XULContentRegistry tokenId
     * @return contentId
     */
    function listContent(
        uint256 _priceInXUL,
        string calldata _contentRef,
        uint256 _registryTokenId
    ) external returns (bytes32) {
        require(_priceInXUL > 0, "Price must be positive");
        require(bytes(_contentRef).length > 0, "Content reference required");

        bytes32 contentId = keccak256(
            abi.encode(msg.sender, nextContentId++, block.timestamp)
        );

        listings[contentId] = ContentListing({
            creator:         msg.sender,
            priceInXUL:      _priceInXUL,
            totalEarned:     0,
            unlockCount:     0,
            isActive:        true,
            contentRef:      _contentRef,
            registryTokenId: _registryTokenId
        });

        emit ContentListed(contentId, msg.sender, _priceInXUL, _contentRef, _registryTokenId);

        return contentId;
    }

    /**
     * @notice 更新内容价格或上下架状态
     */
    function updateContent(
        bytes32 _contentId,
        uint256 _newPrice,
        bool _isActive
    ) external {
        require(listings[_contentId].creator == msg.sender, "Only creator");
        if (_newPrice > 0) listings[_contentId].priceInXUL = _newPrice;
        listings[_contentId].isActive = _isActive;
        emit ContentUpdated(_contentId, _newPrice, _isActive);
    }

    /**
     * @notice 解锁内容（调用前需先 approve 本合约）
     * @dev 用户需提前 approve 本合约提取 XUL
     */
    function unlockContent(bytes32 _contentId) external {
        ContentListing storage listing = listings[_contentId];
        require(listing.isActive, "Content not active");
        require(!unlocked[_contentId][msg.sender], "Already unlocked");

        uint256 price = listing.priceInXUL;
        require(price > 0, "Not listed");

        // 从买家账户转走 XUL
        require(
            IXULToken(XUL_TOKEN).transferFrom(msg.sender, address(this), price),
            "Transfer failed"
        );

        // 即时转给创作者（保留 0 gas fee）
        require(
            IXULToken(XUL_TOKEN).transfer(listing.creator, price),
            "Creator transfer failed"
        );

        listing.totalEarned += price;
        listing.unlockCount++;
        unlocked[_contentId][msg.sender] = true;

        emit ContentUnlocked(
            _contentId,
            msg.sender,
            listing.creator,
            price,
            listing.unlockCount
        );
    }

    /**
     * @notice 查询用户是否已解锁某内容
     */
    function hasUnlocked(bytes32 _contentId, address _user) external view returns (bool) {
        return unlocked[_contentId][_user];
    }

    /**
     * @notice 获取内容完整信息
     */
    function getListing(bytes32 _contentId) external view returns (
        address  creator,
        uint256  priceInXUL,
        uint256  totalEarned,
        uint256  unlockCount,
        bool     isActive,
        string   memory contentRef,
        uint256  registryTokenId
    ) {
        ContentListing memory l = listings[_contentId];
        require(l.creator != address(0), "Not listed");
        return (
            l.creator,
            l.priceInXUL,
            l.totalEarned,
            l.unlockCount,
            l.isActive,
            l.contentRef,
            l.registryTokenId
        );
    }

    /**
     * @notice 查询本合约持有的 XUL 余额（正常应为 0）
     */
    function contractBalance() external view returns (uint256) {
        return IXULToken(XUL_TOKEN).balanceOf(address(this));
    }
}

// ── 内联接口（避免引入 OpenZeppelin）─────────────────────

interface IXULToken {
    function transferFrom(address from, address to, uint256 amount) external returns (bool);
    function transfer(address to, uint256 amount) external returns (bool);
    function balanceOf(address account) external view returns (uint256);
}
