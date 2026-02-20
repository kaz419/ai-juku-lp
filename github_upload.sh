#!/bin/bash

echo "🚀 AI塾LP GitHubアップロードスクリプト"
echo ""

cd /Users/kaz/OpenClaw_File/projects/ai-juku

# index.htmlにコピー
cp LP_landing_page.html index.html

# Gitリポジトリ初期化
git init

# ファイル追加
git add index.html

# コミット
git commit -m "Add AI活用オンライン塾 LP"

# ブランチ名変更
git branch -M main

# リモート追加
git remote add origin https://github.com/kaz419/ai-juku-lp.git

# プッシュ
git push -u origin main

echo ""
echo "✅ 完了！"
echo "次のステップ: GitHub Pagesを有効化してください"
