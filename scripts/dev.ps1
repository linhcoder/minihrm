param([switch]$Seed)
$ErrorActionPreference='Stop'
$root=Split-Path $PSScriptRoot -Parent
Set-Location -LiteralPath $root
$php=Join-Path $root '.tools\php\php.exe'
if (!(Test-Path -LiteralPath $php)) { $php=(Get-Command php).Source }
if ($Seed) { & $php backend\spark migrate; & $php backend\spark db:seed DemoSeeder }
Start-Process -FilePath $php -ArgumentList @('-S','127.0.0.1:8080','-t','backend/public','backend/vendor/codeigniter4/framework/system/rewrite.php') -WorkingDirectory $root -WindowStyle Hidden -RedirectStandardOutput (Join-Path $root '.tools\api-out.log') -RedirectStandardError (Join-Path $root '.tools\api-error.log')
npm.cmd run dev --prefix web
