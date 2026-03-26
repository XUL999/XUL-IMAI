#!/bin/bash
# XUL-IMAI 同步脚本
# 用于从 IMAI.WORK 原版拉取更新

echo "🔄 XUL-IMAI 同步脚本"
echo "===================="

# 检查远程仓库
echo "📡 检查远程仓库..."
git remote -v

# 拉取原版更新
echo ""
echo "📥 拉取 IMAI.WORK 原版更新..."
git fetch upstream

# 显示原版更新
echo ""
echo "📋 原版最新提交:"
git log upstream/master --oneline -5

# 合并更新
echo ""
echo "🔀 合并更新到 XUL-IMAI..."
git merge upstream/master

# 解决冲突后推送
echo ""
echo "📤 推送更新..."
git push origin main

echo ""
echo "✅ 同步完成!"
echo "请检查是否有冲突需要解决。"
