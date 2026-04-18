// 生成 TabBar 图标占位符
// 实际项目需要设计图标

const icons = {
  wallet: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="12" width="40" height="28" rx="4" stroke="#999" stroke-width="2"/><rect x="28" y="22" width="12" height="8" rx="2" stroke="#999" stroke-width="2"/></svg>`,
  walletActive: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="12" width="40" height="28" rx="4" stroke="#007AFF" stroke-width="2"/><rect x="28" y="22" width="12" height="8" rx="2" stroke="#007AFF" stroke-width="2"/></svg>`,
  browser: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="16" stroke="#999" stroke-width="2"/><path d="M24 8v32M8 24h32M14 14l20 20M34 14l-20 20" stroke="#999" stroke-width="1.5"/></svg>`,
  browserActive: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="16" stroke="#007AFF" stroke-width="2"/><path d="M24 8v32M8 24h32M14 14l20 20M34 14l-20 20" stroke="#007AFF" stroke-width="1.5"/></svg>`,
  ai: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="20" r="12" stroke="#999" stroke-width="2"/><path d="M16 38c0-4.4 3.6-8 8-8s8 3.6 8 8" stroke="#999" stroke-width="2"/><circle cx="20" cy="18" r="2" fill="#999"/><circle cx="28" cy="18" r="2" fill="#999"/></svg>`,
  aiActive: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="20" r="12" stroke="#007AFF" stroke-width="2"/><path d="M16 38c0-4.4 3.6-8 8-8s8 3.6 8 8" stroke="#007AFF" stroke-width="2"/><circle cx="20" cy="18" r="2" fill="#007AFF"/><circle cx="28" cy="18" r="2" fill="#007AFF"/></svg>`,
  settings: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="6" stroke="#999" stroke-width="2"/><path d="M24 4v8M24 36v8M44 24h-8M12 24H4M38.6 9.4l-5.6 5.6M15 33l-5.6 5.6M38.6 38.6l-5.6-5.6M15 15l-5.6-5.6" stroke="#999" stroke-width="2" stroke-linecap="round"/></svg>`,
  settingsActive: `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="6" stroke="#007AFF" stroke-width="2"/><path d="M24 4v8M24 36v8M44 24h-8M12 24H4M38.6 9.4l-5.6 5.6M15 33l-5.6 5.6M38.6 38.6l-5.6-5.6M15 15l-5.6-5.6" stroke="#007AFF" stroke-width="2" stroke-linecap="round"/></svg>`,
}

console.log('图标 SVG 定义已创建')
console.log('需要在设计工具中导出 PNG 格式')
