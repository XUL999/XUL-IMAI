#!/bin/bash
# XUL-AI 同步脚本
# 用于从上游拉取更新

echo "🔄 XUL-AI 同步脚本"
echo "=================="

# 检查远程仓库
echo "📡 检查远程仓库..."
git remote -v

# 拉取上游更新
echo ""
echo "📥 拉取上游更新..."
git fetch upstream

# 显示上游最新提交
echo ""
echo "📋 上游最新提交:"
git log upstream/master --oneline -5

# 合并更新
echo ""
echo "🔀 合并更新到 XUL-AI..."
git merge upstream/master

# 解决冲突后推送
echo ""
echo "📤 推送更新..."
git push origin main

echo ""
echo "✅ 同步完成!"
echo "请检查是否有冲突需要解决。"
