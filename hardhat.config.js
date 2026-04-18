require("@nomicfoundation/hardhat-ethers");
require("dotenv/config");

/** @type import('hardhat/config').HardhatUserConfig */
module.exports = {
  paths: {
    sources: "./contracts",
    artifacts: "./artifacts",
  },
  networks: {
    xul: {
      url: process.env.XUL_RPC_URL || "https://pro.rswl.ai",
      chainId: 12309,
      accounts: process.env.DEPLOYER_PRIVATE_KEY ? [process.env.DEPLOYER_PRIVATE_KEY] : [],
      gasPrice: 100000000,
    },
    hardhat: {
      chainId: 12309,
      forking: {
        url: "https://pro.rswl.ai",
        blockNumber: 932000,
      },
    },
  },
  solidity: {
    version: "0.8.28",
    settings: {
      optimizer: { enabled: true, runs: 200 },
      viaIR: true,
      evmVersion: "paris",
    },
  },
};
