---
description: Execute terminal commands via file-based bridge (workaround for VS Code terminal hanging)
---

# Terminal Bridge Workflow

// turbo-all

This workflow uses a file-based bridge to execute terminal commands when the VS Code terminal integration is hanging.

## Prerequisites

The user must have the bridge script running in their terminal:
```powershell
powershell -ExecutionPolicy Bypass -File .\.agent\tools\terminal_bridge.ps1
```

## How to Execute a Command

1. Write the command to the input file. Include `CWD:` line if a specific working directory is needed:

```
CWD:f:\TEAM\agentic-rag
go build ./...
```

The file to write to: `.agent/tmp/cmd_input.txt`

2. Wait 2-3 seconds for the command to execute.

3. Read the output file: `.agent/tmp/cmd_output.txt`

The output format is:
```
STATUS:DONE
EXIT_CODE:0
TIME:1.2s
---OUTPUT---
<actual command output here>
```

4. If `STATUS:RUNNING`, wait and read again. If `STATUS:DONE`, the command has finished.

## Important Notes

- Always use `write_to_file` with `Overwrite: true` to write commands
- Always use `view_file` to read results
- The bridge script must be running in the user's terminal
- Commands are executed via `cmd /c` so use Windows command syntax
