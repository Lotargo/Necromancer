# ============================================================
# Agent Terminal Bridge v1.1
# ============================================================
# This script bridges the AI agent and the terminal.
# The agent writes commands to an input file, this script
# executes them and writes results to an output file.
#
# Usage: Run this script in your terminal:
#   powershell -ExecutionPolicy Bypass -File .\.agent\tools\terminal_bridge.ps1
# ============================================================

$ErrorActionPreference = "Continue"

# PSScriptRoot = .agent/tools, so go up 2 levels to project root
$projectRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot "..\..\"))
$agentDir = Join-Path $projectRoot ".agent\tmp"
$inputFile = Join-Path $agentDir "cmd_input.txt"
$outputFile = Join-Path $agentDir "cmd_output.txt"

# Create tmp directory if needed
if (-not (Test-Path $agentDir)) {
    New-Item -ItemType Directory -Path $agentDir -Force | Out-Null
}

# Clear/init files
"" | Set-Content $inputFile -NoNewline
"READY" | Set-Content $outputFile

# Command counter
$cmdCount = 0

# --- Startup banner ---
Write-Host ""
Write-Host "  +=============================================+" -ForegroundColor Cyan
Write-Host "  |     Agent Terminal Bridge v1.1              |" -ForegroundColor Cyan
Write-Host "  +=============================================+" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Status:  " -NoNewline
Write-Host "ACTIVE" -ForegroundColor Green
Write-Host "  Root:    $projectRoot" -ForegroundColor DarkGray
Write-Host "  Input:   $inputFile" -ForegroundColor DarkGray
Write-Host "  Output:  $outputFile" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  [*] Waiting for agent commands..." -ForegroundColor Yellow
Write-Host "  Press Ctrl+C to stop." -ForegroundColor DarkGray
Write-Host ""
Write-Host "  ---------------------------------------------" -ForegroundColor DarkGray

$lastInputTime = [datetime]::MinValue

while ($true) {
    Start-Sleep -Milliseconds 300

    if (-not (Test-Path $inputFile)) { continue }

    # Check if file was modified
    $fileInfo = Get-Item $inputFile
    if ($fileInfo.LastWriteTime -le $lastInputTime) { continue }

    $content = Get-Content $inputFile -Raw -ErrorAction SilentlyContinue
    if ([string]::IsNullOrWhiteSpace($content)) { continue }

    $lastInputTime = $fileInfo.LastWriteTime

    # Parse command and optional working directory
    $lines = $content.Trim() -split "`n"
    $command = ""
    $workDir = $projectRoot

    foreach ($line in $lines) {
        $trimmed = $line.Trim()
        if ($trimmed -match "^CWD:(.+)$") {
            $workDir = $Matches[1].Trim()
        } else {
            if ($command -ne "") { $command += "`n" }
            $command += $trimmed
        }
    }

    if ([string]::IsNullOrWhiteSpace($command)) { continue }

    $cmdCount++
    $timestamp = Get-Date -Format "HH:mm:ss"

    # --- Log: command header ---
    Write-Host ""
    Write-Host "  +-- Command #$cmdCount [$timestamp] -----------------" -ForegroundColor Cyan
    Write-Host "  | CWD: " -ForegroundColor DarkGray -NoNewline
    Write-Host "$workDir" -ForegroundColor White
    Write-Host "  | CMD: " -ForegroundColor DarkGray -NoNewline
    Write-Host "$command" -ForegroundColor Yellow
    Write-Host "  |" -ForegroundColor DarkGray
    Write-Host "  | [~] Executing..." -ForegroundColor DarkYellow

    # Mark as running
    "STATUS:RUNNING" | Set-Content $outputFile

    try {
        # Execute the command
        $prevLocation = Get-Location
        Set-Location $workDir

        $output = $null
        $sw = [System.Diagnostics.Stopwatch]::StartNew()

        $output = cmd /c "$command 2>&1"
        $exitCode = $LASTEXITCODE
        if ($null -eq $exitCode) { $exitCode = 0 }

        $sw.Stop()
        $elapsed = $sw.Elapsed.TotalSeconds.ToString("F1")

        Set-Location $prevLocation

        # Build output string
        $outputStr = ""
        if ($output) {
            $outputStr = ($output | Out-String).TrimEnd()
        }

        # Write result to file
        $result = "STATUS:DONE`nEXIT_CODE:$exitCode`nTIME:${elapsed}s`n---OUTPUT---`n"
        if ($outputStr) {
            $result += $outputStr
        }
        $result | Set-Content $outputFile -Encoding UTF8

        # --- Log: output ---
        Write-Host "  |" -ForegroundColor DarkGray
        if ($outputStr) {
            $outputLines = $outputStr -split "`n"
            $maxLines = 25
            $shown = 0
            foreach ($ol in $outputLines) {
                if ($shown -ge $maxLines) {
                    $remaining = $outputLines.Count - $maxLines
                    Write-Host "  |   " -ForegroundColor DarkGray -NoNewline
                    Write-Host "... (+$remaining more lines)" -ForegroundColor DarkYellow
                    break
                }
                Write-Host "  |   " -ForegroundColor DarkGray -NoNewline
                Write-Host "$($ol.TrimEnd())" -ForegroundColor Gray
                $shown++
            }
        } else {
            Write-Host "  |   (no output)" -ForegroundColor DarkGray
        }

        # --- Log: result footer ---
        Write-Host "  |" -ForegroundColor DarkGray
        if ($exitCode -eq 0) {
            Write-Host "  +-- [OK] " -ForegroundColor Green -NoNewline
            Write-Host "(exit: $exitCode, time: ${elapsed}s)" -ForegroundColor DarkGray
        } else {
            Write-Host "  +-- [FAIL] " -ForegroundColor Red -NoNewline
            Write-Host "(exit: $exitCode, time: ${elapsed}s)" -ForegroundColor DarkGray
        }
    }
    catch {
        $errorMsg = "STATUS:DONE`nEXIT_CODE:1`nTIME:0s`n---OUTPUT---`nERROR: $_"
        $errorMsg | Set-Content $outputFile -Encoding UTF8

        Write-Host "  |" -ForegroundColor DarkGray
        Write-Host "  |   ERROR: $_" -ForegroundColor Red
        Write-Host "  |" -ForegroundColor DarkGray
        Write-Host "  +-- [EXCEPTION] " -ForegroundColor Red -NoNewline
        Write-Host "(time: 0s)" -ForegroundColor DarkGray
    }

    # Clear input to avoid re-execution
    "" | Set-Content $inputFile -NoNewline

    Write-Host ""
    Write-Host "  ---------------------------------------------" -ForegroundColor DarkGray
    Write-Host "  [*] Waiting... (total commands: $cmdCount)" -ForegroundColor DarkGray
}
