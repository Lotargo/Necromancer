# OpenCode Collaboration Protocol

## 1. Team Structure

**Jules** is the primary agent — the architect and owner of the task.

**opencode** is the collaborating agent — an active contributor with full tool access. Both agents share the same filesystem, tools, and context. Treat opencode as a capable teammate, not a restricted worker pool.

### Ownership Boundaries

| Area | Jules | opencode |
|------|-------|----------|
| Final architecture decisions | Owner | Contributor |
| Production code edits | Owner | Contributor (via patches, drafts) |
| Test scaffolding & test cases | Contributor | **Primary owner** |
| Edge case definition | Contributor | **Primary owner** |
| Documentation | Owner (final wording) | Can draft and write directly |
| PR descriptions | Owner (final approval) | Can draft |
| Code research & analysis | Collaborative | Collaborative |
| Bug investigation | Collaborative | Collaborative |

**Rule:** opencode may write to the filesystem freely within its assigned tasks. Jules retains veto power and final review on all production code and architecture.

### Independent QA Principle (Anti-Creator's-Bias)

To eliminate "Creator's Bias" — where the author of production code writes tests that merely confirm their own assumptions — **opencode is the primary author of all test cases, test scaffolding, and edge-case definitions.**

The workflow is adversarial by design:

1. **Jules** writes the production logic based on requirements.
2. **opencode** writes the tests that must pass, independently derived from requirements — not from reading the implementation.
3. **Jules** must then write production code robust enough to satisfy opencode's tests.

This separation ensures tests validate *behavior*, not *implementation*. opencode acts as an independent QA engineer: objective, requirements-driven, and actively trying to break the code.

**Critical rule:** Jules must never write or modify tests to make failing production code pass. If tests fail, Jules fixes the production code. If tests are wrong, opencode rewrites them with justification.

## 2. When to Use opencode

Use opencode when the work is:

- context-heavy or repetitive
- easy to parallelize
- useful as an independent review
- likely to take more than 5-10 minutes
- blocked by uncertainty where a second opinion helps
- generating boilerplate, scaffolding, or documentation

Do not use opencode just to satisfy a ritual. For tiny tasks, direct inspection is fine.

## 3. What opencode Can Do

### Full Access (write directly)

- Generate `.patch` files for Jules to review and apply
- Write documentation (README, guides, API docs) directly to disk
- Create test scaffolding and test files
- Draft PR descriptions and commit messages
- Generate configuration files and boilerplate
- Write scripts and utility code
- Create session tracking files

### Collaborative Access (discuss first)

- Production source edits — propose via patch or diff, Jules approves
- Bug fixes — investigate and propose, Jules reviews
- Refactors — draft the refactor, Jules validates approach
- Architecture changes — research and recommend, Jules decides

### Read-Only Tasks

- Codebase research and call-site mapping
- Log analysis and failure summarization
- Running tests and reporting output
- Git history inspection (`log`, `blame`, `bisect`)
- Environment validation
- Web research against current documentation

## 4. Agent Roles

Use one focused role per opencode invocation. Roles define the task type, not access level.

### Scout

Find relevant files, functions, call sites, configs, and risks.

```text
You are Scout. Inspect the repository for [topic].
Return relevant files, call sites, assumptions, and risks.
```

### Builder

Generate code, patches, scaffolding, or documentation files.

```text
You are Builder. [Task description].
Write output to [paths].
Follow existing code conventions and patterns.
```

### Tester

Write test cases, test scaffolding, and edge-condition definitions based on requirements — not implementation. Act as an independent QA engineer trying to break the code.

```text
You are Tester (Independent QA). [Requirements or feature description].
Write test files to [paths].
Focus on: edge cases, boundary conditions, error paths, invalid inputs, and adversarial scenarios.
Do not read production code before writing tests — derive tests from requirements only.
Return test file paths, coverage plan, and rationale.
```

### Reviewer

Review a diff or implementation for bugs, regressions, missing tests, and unsafe assumptions.

```text
You are Reviewer. Review the current diff for [goal].
Prioritize correctness bugs, regressions, and missing tests.
Return findings with file paths and line references.
```

### Researcher

Find current official documentation or best practices.

```text
You are Researcher. Find the latest official documentation as of [date] for [topic].
Ignore deprecated approaches.
Return links, short summary, and recommended implementation constraints.
```

### Scribe

Draft documentation or walkthroughs from provided facts.

```text
You are Scribe. Draft documentation for [change].
Use only the facts below. Mark unknowns explicitly.
[facts]
Write to [path].
```

## 5. Output Contract

Every opencode result should be easy to audit. Request:

```text
Summary:
Files created/modified:
Evidence:
Commands run:
Risks or uncertainty:
Recommendation:
```

For patches, include the full diff. For tests, include exact command lines and exit codes. For web research, include source links and dates.

For reviews, use severity labels:

- **P0:** data loss, security issue, crash, or unusable core workflow
- **P1:** likely correctness bug or major regression
- **P2:** edge-case bug, missing validation, or meaningful test gap
- **P3:** maintainability or clarity issue

## 6. Patch Workflow

When opencode modifies production code, prefer the patch workflow:

1. opencode generates a `.patch` file in `.brains/patches/` or `/tmp/`
2. Jules reviews the patch contents
3. Jules applies with `git apply` or `git apply --check`
4. If rejected, Jules modifies or discards

Patch naming: `.brains/patches/YYYY-MM-DD-NNNN-description.patch`

## 7. Session Tracking

Use `.brains/sessions/` for complex or multi-phase work.

```text
.brains/sessions/YYYY-MM-DD-NNNN/
  plans/
  tasks/
  patches/
  walkthrough/
```

Create a session when the task has multiple phases, multiple agents, or a non-trivial verification trail.

Minimum contents:

- `plans/`: what will be changed or investigated
- `tasks/`: checklist with `[ ]`, `[/]`, `[x]`
- `walkthrough/`: final summary after verification

Small tasks do not need a session.

## 8. Parallel Dispatch

Run independent support tasks in parallel, then continue primary work while they run.

Rules:

- one agent = one atomic task
- prefer 1-3 parallel agents
- redirect output to log files
- read and verify logs before using their conclusions

Linux/macOS:

```bash
OPENCODE_SERVER_PASSWORD="" OPENCODE_SERVER_USERNAME="" opencode run --pure "Scout task." > /tmp/scout.log 2>&1 &
OPENCODE_SERVER_PASSWORD="" OPENCODE_SERVER_USERNAME="" opencode run --pure "Tester task." > /tmp/tester.log 2>&1 &
wait
```

PowerShell:

```powershell
$env:OPENCODE_SERVER_PASSWORD = ""
$env:OPENCODE_SERVER_USERNAME = ""
$root = (Get-Location).Path
$j1 = Start-Job -ScriptBlock { param($d) Set-Location $d; opencode run --pure "Scout task." } -ArgumentList $root
$j2 = Start-Job -ScriptBlock { param($d) Set-Location $d; opencode run --pure "Tester task." } -ArgumentList $root
$j1, $j2 | Wait-Job | Receive-Job
```

## 9. Verification Rules

The primary agent must verify:

- files that opencode claims are relevant
- tests that opencode claims passed or failed
- generated documentation against real diffs and logs
- web research against official or primary sources when possible
- any recommendation before implementing it

If opencode gives vague output, ask a narrower follow-up or discard it.

If opencode disagrees with local evidence, local evidence wins.

## 10. Model Selection

**All opencode invocations must use `mimo-v2.5-free` (model ID: `opencode/mimo-v2.5-free`).** No exceptions. Do not switch models based on task type, role, or availability of alternatives. This model has proven to be the most effective and valuable across all task categories — documentation, code analysis, debugging, scaffolding, research, and review.

If `mimo-v2.5-free` is unavailable, **stop and report the issue** rather than silently substituting another model.

## 11. Platform Notes

### Windows PowerShell

If `OPENCODE_SERVER_PASSWORD` is set by OpenCode Desktop, `opencode run` may fail with `Session not found`. Clear both variables before running:

```powershell
$env:OPENCODE_SERVER_PASSWORD = ""
$env:OPENCODE_SERVER_USERNAME = ""
opencode run --pure "Your task."
```

Optional wrapper:

```powershell
function opencode-run {
  $env:OPENCODE_SERVER_PASSWORD = ""
  $env:OPENCODE_SERVER_USERNAME = ""
  opencode run @args
}
```

### Linux/macOS

Optional wrapper:

```bash
opencode-run() {
  OPENCODE_SERVER_PASSWORD="" OPENCODE_SERVER_USERNAME="" opencode run "$@"
}
```

## 12. Anti-Patterns

Avoid:

- asking one agent to solve five unrelated tasks
- accepting summaries without evidence
- using web results without checking recency and source quality
- creating a session folder for trivial work
- waiting idly when independent work can continue
- treating opencode as persistent memory
- over-reviewing small changes — trust the agent, verify the result

## 13. Dataset Lessons

After completing any non-trivial task, **Jules must invoke opencode** (as Builder) to generate a `.dataset/lessons/` entry. This is mandatory, not optional. The purpose is to build a universally valuable dataset for fine-tuning — records must be self-contained, not dependent on local repo state.

Storage:

```text
.dataset/lessons/
```

Schema:

```json
{
  "topic": "Concise title (e.g., 'Prefer explicit over implicit return')",
  "principle": "The universal lesson or best practice learned, stated abstractly",
  "instruction": "What a developer might ask or encounter that triggers this lesson",
  "bad_example": "Self-contained minimal code or text demonstrating the anti-pattern",
  "good_example": "Self-contained minimal code or text demonstrating the correct approach",
  "tags": ["list", "of", "relevant", "tags"]
}
```

Rules:

- write in English
- use snake_case file names for lesson files
- **`principle` must be generalizable** — no file paths, line numbers, or repo-specific references
- **`bad_example` and `good_example` must be self-contained** — a reader should understand them without needing surrounding code or repo context
- do not include secrets, private tokens, or unrelated user data
- primary agent must read and verify the JSON before accepting it
