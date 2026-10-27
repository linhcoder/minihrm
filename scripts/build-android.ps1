param([string]$Gradle='',[string]$Sdk=$env:ANDROID_HOME)
$ErrorActionPreference='Stop'
$projectRoot=Split-Path $PSScriptRoot -Parent
if (!$Sdk) { $Sdk=Join-Path $env:LOCALAPPDATA 'Android\Sdk' }
if (!(Test-Path -LiteralPath $Sdk)) { throw 'Set ANDROID_HOME to an installed Android SDK.' }
$toolRoot=Join-Path $projectRoot '.tools'
New-Item -ItemType Directory -Force (Join-Path $toolRoot 'android-user'),(Join-Path $toolRoot 'java-home') | Out-Null
$env:ANDROID_USER_HOME=Join-Path $toolRoot 'android-user'
Remove-Item Env:\ANDROID_PREFS_ROOT -ErrorAction SilentlyContinue
$env:GRADLE_USER_HOME=Join-Path $toolRoot 'gradle-home'
$env:ANDROID_HOME=$Sdk
$sdkProperty=$Sdk.Replace('\','/').Replace(':','\:')
[System.IO.File]::WriteAllText((Join-Path $projectRoot 'android\local.properties'),"sdk.dir=$sdkProperty`n")
Remove-Item Env:\JAVA_TOOL_OPTIONS -ErrorAction SilentlyContinue
$env:GRADLE_OPTS='-Duser.home='+(Join-Path $toolRoot 'java-home')
$env:DEBUG=''
if (!$Gradle) { $Gradle=Join-Path $projectRoot 'android\gradlew.bat' }
$ErrorActionPreference='Continue'
& $Gradle -p (Join-Path $projectRoot 'android') --no-daemon :app:assembleDebug :app:testDebugUnitTest :app:lintDebug --stacktrace
if ($LASTEXITCODE -ne 0) { throw 'Android verification failed.' }
