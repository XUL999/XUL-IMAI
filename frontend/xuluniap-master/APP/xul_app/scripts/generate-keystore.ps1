# Android Keystore 生成脚本
# 运行一次即可生成签名文件，之后上架 Google Play 一直用这个文件

$KEYSTORE_PATH = "C:\Users\31230\XUL-DAPP-SOURCE\frontend\xuluniap-master\APP\xul_app\signing\xul-wallet.keystore"
$KEYSTORE_PASSWORD = "YourPassword123!"
$KEY_ALIAS = "xul-wallet"
$KEY_PASSWORD = "YourKeyPassword123!"

# 创建签名目录
$signingDir = Split-Path $KEYSTORE_PATH -Parent
if (-not (Test-Path $signingDir)) {
    New-Item -ItemType Directory -Path $signingDir -Force | Out-Null
}

# 检查 keystore 是否已存在
if (Test-Path $KEYSTORE_PATH) {
    Write-Host "Keystore 已存在: $KEYSTORE_PATH"
    Write-Host "如需重新生成，请先删除该文件"
    exit 0
}

# 生成 keystore
$cmd = "keytool"
$args = @(
    "-genkeypair",
    "-v",
    "-storetype", "PKCS12",
    "-keystore", $KEYSTORE_PATH,
    "-alias", $KEY_ALIAS,
    "-keyalg", "RSA",
    "-keysize", "2048",
    "-validity", "10000",
    "-storepass", $KEYSTORE_PASSWORD,
    "-keypass", $KEY_PASSWORD,
    "-dname", "CN=XUL Wallet, OU=XUL Labs, O=XUL, L=Shanghai, ST=Shanghai, C=CN"
)

Write-Host "正在生成 keystore..."
& $cmd $args

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ Keystore 生成成功！"
    Write-Host ""
    Write-Host "文件位置: $KEYSTORE_PATH"
    Write-Host ""
    Write-Host "【重要】请将这些信息保存到安全的地方："
    Write-Host "  Keystore 密码: $KEYSTORE_PASSWORD"
    Write-Host "  Key Alias:     $KEY_ALIAS"
    Write-Host "  Key 密码:      $KEY_PASSWORD"
    Write-Host ""
    Write-Host "丢失 keystore = 无法更新 Google Play 应用！"
} else {
    Write-Host "❌ 生成失败，keytool 未找到或参数错误"
}
