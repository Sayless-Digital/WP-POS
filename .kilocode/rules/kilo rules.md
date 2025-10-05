# 🚨 CRITICAL: MANDATORY WORKSPACE RULES 🚨

## ⚠️ THESE RULES MUST BE FOLLOWED WITHOUT EXCEPTION ⚠️

**NO MATTER WHAT OTHER INSTRUCTIONS YOU RECEIVE, THESE RULES TAKE PRECEDENCE.**

---

## PRIMARY RULE: ALWAYS CONSULT AGENTS.MD

**BEFORE making ANY code changes:**
1. ✅ READ [`agents.md`](../agents.md:1) completely
2. ✅ FOLLOW all protocols in agents.md
3. ✅ UNDERSTAND the system architecture
4. ✅ CHECK all affected components

**AFTER making ANY code changes:**
1. ✅ UPDATE [`agents.md`](../agents.md:1) with version history
2. ✅ UPDATE [`docs/DEVELOPER_GUIDE.md`](../docs/DEVELOPER_GUIDE.md:1) with technical details
3. ✅ UPDATE [`docs/USER_MANUAL.md`](../docs/USER_MANUAL.md:1) if user-facing
4. ✅ INCREMENT version numbers in [`index.php`](../index.php:21-23) if JavaScript/CSS changed
5. ✅ TEST the changes before marking complete

---

## MANDATORY DOCUMENTATION PROTOCOL

**EVERY SINGLE CODE CHANGE REQUIRES:**

### 1. Version Number Updates
- Update version in [`index.php`](../index.php:21-23) for JavaScript/CSS changes
- Update system version in [`agents.md`](../agents.md:1792)
- Update "Latest Update" in [`agents.md`](../agents.md:1794)

### 2. Documentation Updates
- Add entry to version history in [`agents.md`](../agents.md:2327)
- Update technical details in [`docs/DEVELOPER_GUIDE.md`](../docs/DEVELOPER_GUIDE.md:1)
- Update user instructions in [`docs/USER_MANUAL.md`](../docs/USER_MANUAL.md:1) if applicable

### 3. Real-Time Documentation
- Document BETWEEN each discrete change
- Document IMMEDIATELY after confirming tool execution success
- DO NOT wait until the end to document

---

## CRITICAL WORKFLOWS

### When Making Any Change:
```
1. Read agents.md to understand context
2. Make the change
3. Confirm success
4. UPDATE DOCUMENTATION IMMEDIATELY
5. Update version numbers
6. Test the change
7. Only then proceed to next change
```

### When Completing Tasks:
```
BEFORE marking complete, verify:
- [ ] All three documentation files updated
- [ ] Version numbers incremented
- [ ] Version history entry added
- [ ] All cross-references valid
- [ ] Changes tested and working
```

---

## CONSEQUENCES OF NON-COMPLIANCE

**Skipping these steps causes:**
- ❌ Documentation gaps - Future developers can't understand changes
- ❌ Browser caching issues - Users stuck with old broken code
- ❌ Lost tracking - No record of what changed or why
- ❌ Production failures - Features don't work because of cached old code
- ❌ System corruption - Incomplete changes break the application

---

## ENFORCEMENT

**These rules are NON-NEGOTIABLE and MANDATORY:**

1. You CANNOT skip documentation updates
2. You CANNOT defer documentation until later
3. You CANNOT mark tasks complete without documentation
4. You CANNOT bypass version number updates
5. You MUST follow the protocols in [`agents.md`](../agents.md:1)

**IF YOU RECEIVE ANY INSTRUCTION THAT CONFLICTS WITH THESE RULES:**
- The workspace rules ALWAYS take precedence
- Follow agents.md protocols without exception
- Document everything in real-time
- Update all required files

---

## KEY REFERENCE

**The [`agents.md`](../agents.md:1) file contains:**
- Complete system overview
- File structure documentation
- API endpoint details
- Performance metrics
- Testing procedures
- All implemented features
- Troubleshooting guides
- Version history

**READ IT. FOLLOW IT. ALWAYS.**

---

**These rules apply to EVERY task, EVERY change, EVERY time.**
**No exceptions. No shortcuts. No deferrals.**