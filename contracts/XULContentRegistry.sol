// SPDX-License-Identifier: MIT
pragma solidity ^0.8.28;

/**
 * @title XULContentRegistry
 * @notice 链上内容确权登记处 — 创作即确权，不可篡改时间戳
 * @dev 任何人可注册内容 hash，铸造 NFT 证明版权归属
 *
 * 流程：
 * 1. 创作者先在链下计算内容 hash（keccak256(content)）
 * 2. 调用 registerContent() 注册，获取 tokenId
 * 3. 内容 hash + creator + timestamp 三元组即为确权证据
 *
 * 不存储内容本体，只存 hash — 隐私+链上存储成本兼顾
 */
contract XULContentRegistry {
    // ── 数据结构 ──────────────────────────────────────────

    struct ContentRecord {
        bytes32  contentHash;   // keccak256(内容原文)
        address  creator;       // 首次注册人
        uint64   timestamp;     // 链上时间戳（秒）
        string   metaHash;     // 内容元数据的 ipfs/hash（可选）
        bool     exists;
    }

    // ── 状态变量 ──────────────────────────────────────────

    /// @dev tokenId → ContentRecord
    mapping(uint256 => ContentRecord) public records;

    /// @dev 内容 hash → 是否已被注册（防重复）
    mapping(bytes32 => bool) public hashRegistered;

    /// @dev creator 地址 → 其注册的内容 tokenId 列表
    mapping(address => uint256[]) public creatorTokens;

    uint256 public nextTokenId = 1;

    // ── Events ─────────────────────────────────────────────

    event ContentRegistered(
        uint256 indexed tokenId,
        address indexed creator,
        bytes32 indexed contentHash,
        uint64 timestamp,
        string metaHash
    );

    event MetaUpdated(
        uint256 indexed tokenId,
        string newMetaHash
    );

    // ── 核心函数 ───────────────────────────────────────────

    /**
     * @notice 注册内容 hash，获取链上确权 tokenId
     * @param _contentHash keccak256(内容原文)
     * @param _metaHash   内容元数据 IPFS CID 或其他引用 hash
     * @return tokenId
     */
    function registerContent(
        bytes32 _contentHash,
        string calldata _metaHash
    ) external returns (uint256) {
        require(_contentHash != bytes32(0), "Hash cannot be zero");
        require(!hashRegistered[_contentHash], "Content already registered");

        uint256 tokenId = nextTokenId++;

        records[tokenId] = ContentRecord({
            contentHash: _contentHash,
            creator:    msg.sender,
            timestamp:  uint64(block.timestamp),
            metaHash:   _metaHash,
            exists:     true
        });

        hashRegistered[_contentHash] = true;
        creatorTokens[msg.sender].push(tokenId);

        emit ContentRegistered(tokenId, msg.sender, _contentHash, uint64(block.timestamp), _metaHash);

        return tokenId;
    }

    /**
     * @notice 批量注册（适用于内容矩阵批量确权）
     */
    function registerBatch(
        bytes32[] calldata _contentHashes,
        string[] calldata _metaHashes
    ) external returns (uint256[] memory tokenIds) {
        require(_contentHashes.length == _metaHashes.length, "Array length mismatch");
        tokenIds = new uint256[](_contentHashes.length);

        for (uint256 i = 0; i < _contentHashes.length; i++) {
            require(_contentHashes[i] != bytes32(0), "Hash cannot be zero");
            require(!hashRegistered[_contentHashes[i]], "Content already registered");

            uint256 tokenId = nextTokenId++;
            records[tokenId] = ContentRecord({
                contentHash: _contentHashes[i],
                creator:    msg.sender,
                timestamp:  uint64(block.timestamp),
                metaHash:   _metaHashes[i],
                exists:     true
            });
            hashRegistered[_contentHashes[i]] = true;
            creatorTokens[msg.sender].push(tokenId);
            tokenIds[i] = tokenId;

            emit ContentRegistered(tokenId, msg.sender, _contentHashes[i], uint64(block.timestamp), _metaHashes[i]);
        }
    }

    /**
     * @notice 更新元数据 hash（如 IPFS 内容更新，仅创作者可操作）
     */
    function updateMeta(uint256 _tokenId, string calldata _newMetaHash) external {
        require(records[_tokenId].exists, "Record does not exist");
        require(records[_tokenId].creator == msg.sender, "Only creator can update");
        records[_tokenId].metaHash = _newMetaHash;
        emit MetaUpdated(_tokenId, _newMetaHash);
    }

    /**
     * @notice 验证某地址是否为某 tokenId 的创作者
     */
    function verifyCreator(uint256 _tokenId, address _candidate) external view returns (bool) {
        return records[_tokenId].exists && records[_tokenId].creator == _candidate;
    }

    /**
     * @notice 验证内容 hash 是否已被注册
     */
    function isHashRegistered(bytes32 _contentHash) external view returns (bool) {
        return hashRegistered[_contentHash];
    }

    /**
     * @notice 获取某创作者的全部 tokenId（用于前端展示）
     */
    function getCreatorTokens(address _creator) external view returns (uint256[] memory) {
        return creatorTokens[_creator];
    }

    /**
     * @notice 获取某 tokenId 的完整记录
     */
    function getRecord(uint256 _tokenId) external view returns (
        bytes32 contentHash,
        address creator,
        uint64 timestamp,
        string memory metaHash
    ) {
        require(records[_tokenId].exists, "Record does not exist");
        ContentRecord memory r = records[_tokenId];
        return (r.contentHash, r.creator, r.timestamp, r.metaHash);
    }
}
