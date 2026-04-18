const fs = require('fs');
const path = require('path');

const staticDir = path.join(__dirname, '..', 'src', 'static');

// 1x1 透明 PNG
const transparentPng = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
  'base64'
);

// 创建头像占位
fs.writeFileSync(path.join(staticDir, 'ai-avatar.png'), transparentPng);
fs.writeFileSync(path.join(staticDir, 'user-avatar.png'), transparentPng);

console.log('Created avatar placeholders');
