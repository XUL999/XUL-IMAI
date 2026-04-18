// SPDX-License-Identifier: MIT
pragma solidity ^0.8.28;

/**
 * @title MockXULToken
 * @notice 测试用 XUL ERC-20 模拟合约
 */
contract MockXULToken {
    mapping(address => uint256) private _balances;
    mapping(address => mapping(address => uint256)) private _allowances;
    uint256 private _totalSupply;

    string public name     = "XUL Token";
    string public symbol   = "XUL";
    uint8  public decimals = 18;

    event Transfer(address indexed from, address indexed to, uint256 value);
    event Approval(address indexed owner, address indexed spender, uint256 value);

    constructor() {
        _mint(msg.sender, 1_000_000_000e18); // 10亿初始供应
    }

    function totalSupply() external view returns (uint256) { return _totalSupply; }
    function balanceOf(address a) external view returns (uint256) { return _balances[a]; }
    function allowance(address o, address s) external view returns (uint256) { return _allowances[o][s]; }

    function transfer(address to, uint256 v) external returns (bool) {
        _transfer(msg.sender, to, v); return true;
    }
    function transferFrom(address from, address to, uint256 v) external returns (bool) {
        _spendAllowance(from, msg.sender, v); _transfer(from, to, v); return true;
    }
    function approve(address spender, uint256 v) external returns (bool) {
        _allowances[msg.sender][spender] = v; emit Approval(msg.sender, spender, v); return true;
    }

    function mint(address to, uint256 v) external { _mint(to, v); }

    function _transfer(address from, address to, uint256 v) internal {
        require(_balances[from] >= v, "insufficient balance");
        _balances[from] -= v; _balances[to] += v; emit Transfer(from, to, v);
    }
    function _spendAllowance(address o, address s, uint256 v) internal {
        uint256 a = _allowances[o][s]; require(a >= v, "insufficient allowance");
        _allowances[o][s] = a - v;
    }
    function _mint(address to, uint256 v) internal {
        _balances[to] += v; _totalSupply += v; emit Transfer(address(0), to, v);
    }
}
