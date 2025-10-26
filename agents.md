
# Agent Operating Instructions

## 🚨 CRITICAL INSTRUCTIONS - READ FIRST 🚨

### MANDATORY DOCUMENTATION UPDATE PROTOCOL

**EVERY TIME YOU MAKE A CODE CHANGE, YOU MUST UPDATE ALL THREE DOCUMENTATION FILES:**

1. ✅ **agents.md** (this file) - System version at line 1792 and version history at line 2327
2. ✅ **docs/DEVELOPER_GUIDE.md** - Technical documentation with API details and troubleshooting
3. ✅ **docs/USER_MANUAL.md** - User-facing documentation (if change affects users)

**NO EXCEPTIONS. This is not optional.**

### MANDATORY VERSION NUMBER UPDATE PROTOCOL

**EVERY TIME YOU MODIFY CLIENT-SIDE FILES (main.js, routing.js, etc.), YOU MUST:**

1. ✅ Update version parameter in **index.php** (lines 21-23)
2. ✅ Update system version in **agents.md** (line 1792)
3. ✅ Update "Latest Update" in **agents.md** (line 1794)
4. ✅ Add version history entry in **agents.md** (line 2327)

**Failing to update versions causes browser caching issues where users get old code even after updates.**

### CONSEQUENCES OF SKIPPING THESE STEPS

- ❌ **Documentation gaps** - Future developers can't understand system changes
- ❌ **Browser caching issues** - Users stuck with old, broken code
- ❌ **Lost tracking** - No record of what was changed or why
- ❌ **Production failures** - Features don't work because of cached old code

### QUICK CHECKLIST

Before marking ANY task complete, verify:

- [ ] Updated agents.md with version entry and feature summary
- [ ] Updated docs/DEVELOPER_GUIDE.md with technical details
- [ ] Updated docs/USER_MANUAL.md if user-facing change
- [ ] Updated version numbers in index.php if JavaScript/CSS changed
- [ ] Incremented system version in agents.md
- [ ] Added version history entry to agents.md

**If you can't check ALL boxes above, the task is NOT complete.**

---

## Table of Contents
1. [Agent Core Purpose & Capabilities](#agent-core-purpose--capabilities)
2. [Documentation Navigation & Utilization](#documentation-navigation--utilization)
3. [Real-Time Documentation Protocols](#real-time-documentation-protocols)
4. [Formatting Standards & Templates](#formatting-standards--templates)
5. [Documentation Detail Requirements](#documentation-detail-requirements)
6. [Concrete Examples](#concrete-examples)
7. [Version Management & Cache Busting Protocol](#version-management--cache-busting-protocol)
8. [Interconnected Changes Protocol](#interconnected-changes-protocol)

---

## Agent Core Purpose & Capabilities

### Your Role
You are an AI agent working on the **WP POS (WordPress Point of Sale)** system located at `/home/u479157563/domains/jonesytt.com/public_html/wp-pos`. Your primary responsibilities include:

- **Code Development**: Writing, modifying, and refactoring PHP, JavaScript, CSS, and HTML code
- **Bug Fixes**: Identifying and resolving issues across the entire codebase
- **Feature Implementation**: Adding new functionality while maintaining system integrity
- **Documentation Maintenance**: Keeping all documentation current and accurate in real-time
- **Testing**: Ensuring changes work correctly before marking tasks complete
- **Architecture Decisions**: Making informed decisions about code structure and design patterns

### Available Capabilities

#### File Operations
- **Read Files**: Access any file in the codebase to understand context
- **Write/Modify Files**: Create new files or modify existing ones using [`apply_diff`](api/validation.php:1), [`write_to_file`](config/wp-pos-config.json:1), [`insert_content`](index.php:1), and [`search_and_replace`](assets/js/main.js:1)
- **Search Files**: Use regex patterns to find code across the project with [`search_files`](docs/DEVELOPER_GUIDE.md:1)
- **List Structure**: Navigate directory structures with [`list_files`](docs/USER_MANUAL.md:1) and [`list_code_definition_names`](api/products.php:1)

#### Code Analysis
- **Read Multiple Files**: Review up to 5 files simultaneously to understand related implementations
- **Pattern Search**: Find specific code patterns, functions, or configurations across the entire codebase
- **Definition Mapping**: List all classes, functions, and methods in source files to understand architecture

#### System Operations
- **Execute Commands**: Run CLI commands, tests, build processes, and database operations
- **Browser Testing**: Launch browsers, interact with UI elements, and verify functionality
- **Performance Monitoring**: Track execution times, memory usage, and system metrics

#### Documentation Tools
- **Markdown Editing**: Update all `.md` files in the [`@docs/`](docs/) directory
- **Cross-Referencing**: Link between documentation files and code files
- **Version Tracking**: Maintain accurate version history and changelog entries

### Project Context
This is a **production-ready enterprise POS system** (v1.8.17) with:
- **739 products** in the catalog with optimized loading (20 per page)
- **Modular JavaScript architecture** with centralized state management ([`appState`](assets/js/modules/state.js:1))
- **Comprehensive API layer** with 15+ endpoints in [`api/`](api/)
- **Advanced caching system** using [`WP-POS_Cache_Manager`](api/cache-manager.php:1)
- **Performance monitoring** via [`WP-POS_Performance_Monitor`](api/performance-monitor.php:1)
- **Real-time logging** to [`logs/`](logs/) directory

---

## Documentation Navigation & Utilization

### Documentation Structure

The [`@docs/`](docs/) directory contains two primary documentation files that serve different audiences:

#### 1. DEVELOPER_GUIDE.md - Technical Reference
**When to Use:**
- Understanding API endpoints and their parameters
- Learning about system architecture and design patterns
- Implementing new features or modifying existing code
- Troubleshooting technical issues (database, caching, routing)
- Setting up development environment
- Running tests and debugging

**Key Sections:**
- **Architecture** (lines 8-19): Frontend/backend structure, state management
- **File Structure** (lines 21-44): Complete directory layout
- **API Reference** (lines 130-329): All endpoints with request/response examples
- **Routing System** (lines 69-128): URL parameter navigation implementation
- **Performance Optimization** (lines 373-408): Database, caching, bundling strategies
- **Testing** (lines 359-372): Test suite structure and execution
- **Troubleshooting** (lines 466-595): Common issues and solutions

#### 2. USER_MANUAL.md - End-User Guide
**When to Use:**
- Understanding user-facing features and workflows
- Learning how end-users interact with the system
- Writing user-facing error messages or help text
- Testing user experience and interface
- Training support staff

**Key Sections:**
- **Getting Started** (lines 3-24): Login and navigation basics
- **Product Management** (lines 26-100): Browsing, editing, attribute management
- **Sales Process** (lines 102-126): Cart management and payment processing
- **Order Management** (lines 128-146): Order history and status tracking
- **Troubleshooting** (lines 209-362): User-facing issues and solutions

#### 3. agents.md - System Overview (This File)
**When to Use:**
- Getting comprehensive system overview
- Understanding complete architecture
- Reviewing all implemented features
- Checking system status and version history
- Finding complete file structure reference

**Key Sections:**
- **System Overview** (lines after this opening section)
- **Architecture** (Frontend/Backend)
- **File Structure** (Complete tree)
- **API Endpoints** (Summary with links to DEVELOPER_GUIDE.md)
- **Performance Metrics** (Current benchmarks)
- **Complete Feature Summary** (All implemented features)

### Cross-Referencing Protocol

#### 🚨 CRITICAL: When Documenting Code Changes

**YOU MUST UPDATE ALL THREE DOCUMENTATION FILES:**

1. ✅ **Update agents.md**: Add to version history (line 2327), update feature summary, update system version (line 1792)
2. ✅ **Update docs/DEVELOPER_GUIDE.md**: Add technical details, API changes, troubleshooting
3. ✅ **Update docs/USER_MANUAL.md** (if user-facing): Add usage instructions, screenshots needs

**This is MANDATORY, not optional. All three files must be kept in sync.**

#### Example Cross-Reference Flow
```markdown
<!-- In agents.md -->
- v1.8.18: Implemented product search optimization with fuzzy matching

<!-- In DEVELOPER_GUIDE.md -->
### Product Search API
- **Fuzzy Matching**: Uses Levenshtein distance algorithm for typo tolerance
- See [Product Management](docs/USER_MANUAL.md#product-management) for user guide

<!-- In USER_MANUAL.md -->
### Searching Products
- Type product names even with typos - the system automatically corrects them
- Technical details in [API Reference](docs/DEVELOPER_GUIDE.md#product-endpoints)
```

### Quick Information Location Guide

| Need to Find... | Look in... | Specific Section |
|----------------|------------|------------------|
| API endpoint syntax | DEVELOPER_GUIDE.md | API Reference (lines 130+) |
| Database schema | DEVELOPER_GUIDE.md | Database Schema (lines 344+) |
| User workflow | USER_MANUAL.md | Respective feature section |
| System architecture | agents.md OR DEVELOPER_GUIDE.md | Architecture section |
| Error messages | DEVELOPER_GUIDE.md | Troubleshooting (lines 466+) |
| File locations | agents.md | File Structure (lines 31+) |
| Version history | agents.md OR DEVELOPER_GUIDE.md | Version History (bottom) |
| Performance metrics | agents.md | Performance Metrics section |

---

## Real-Time Documentation Protocols

### ⚠️ CRITICAL: Real-Time Documentation Mandate

**YOU MUST DOCUMENT CHANGES AS YOU MAKE THEM, NOT AFTER THE FACT.**

**UPDATE ALL THREE DOCUMENTATION FILES (agents.md, docs/DEVELOPER_GUIDE.md, docs/USER_MANUAL.md)**

This means:
- Document **between each discrete change** to the codebase
- Update documentation **before marking a step complete**
- Maintain documentation **during multi-step changes**, not at the end
- Update relevant documentation **immediately after confirming successful tool execution**
- **UPDATE VERSION NUMBERS** in index.php (lines 21-23) if JavaScript/CSS files changed
- **UPDATE SYSTEM VERSION** in agents.md (line 1792) after every change

### What Constitutes a "Change" Requiring Documentation

Every single instance of these actions requires immediate documentation:

1. **File Creation**: New files in [`api/`](api/), [`assets/js/`](assets/js/), [`config/`](config/), [`tests/`](tests/), or root directory
2. **Function Addition**: New functions, methods, or classes
3. **Function Modification**: Changes to function parameters, logic, or return values
4. **API Endpoint Changes**: New endpoints, modified parameters, or changed response formats
5. **Configuration Updates**: Changes to [`config/wp-pos-config.json`](config/wp-pos-config.json:1)
6. **Bug Fixes**: Any correction to existing functionality
7. **Performance Optimizations**: Caching, query optimization, or bundle improvements
8. **Security Changes**: Authentication, validation, or CSRF protection updates
9. **UI Modifications**: Layout, styling, or interaction changes
10. **Database Schema Changes**: New tables, fields, or relationships

### Documentation Workflow

#### Step-by-Step Process

**INCORRECT Approach (Do NOT Do This):**
```
1. Make 5 file changes
2. Test everything
3. Document all changes at once
4. Mark task complete
```

**CORRECT Approach (Always Do This):**
```
1. Make first file change (e.g., create api/new-endpoint.php)
2. ✅ Confirm tool execution succeeded
3. 📝 DOCUMENT: Update agents.md version history (line 2327)
4. 📝 DOCUMENT: Update agents.md system version (line 1792)
5. 📝 DOCUMENT: Add to docs/DEVELOPER_GUIDE.md API Reference
6. 📝 DOCUMENT: Update docs/USER_MANUAL.md if user-facing

7. Make second file change (e.g., update assets/js/main.js)
8. ✅ Confirm tool execution succeeded
9. 📝 UPDATE VERSION: Increment version in index.php (lines 21-23)
10. 📝 DOCUMENT: Update agents.md feature summary
11. 📝 DOCUMENT: Add troubleshooting notes to DEVELOPER_GUIDE.md if applicable

12. Continue pattern for each change...
```

#### Autonomous Work Sessions

During autonomous multi-step work, you must:

1. **Pause after each logical unit**: After creating a file, modifying a function, or fixing a bug
2. **Document immediately**: Update all relevant documentation files
3. **Verify documentation**: Ensure cross-references are correct
4. **Continue to next unit**: Only proceed after documentation is complete

**Example Autonomous Session:**
```
Task: "Add product export functionality"

Step 1: Create api/export-products.php
- Wait for confirmation: "File created successfully"
- Update agents.md: Line in File Structure, new API endpoint, version history (line 2327)
- Update agents.md: System version (line 1792)
- Update docs/DEVELOPER_GUIDE.md: Add to API Reference with examples
- Update docs/USER_MANUAL.md: Add to Product Management section

Step 2: Add export button to products page
- Wait for confirmation: "File modified successfully"
- Update version in index.php (line 23) if JavaScript changed
- Update agents.md: Add to Features section
- Update docs/DEVELOPER_GUIDE.md: Add UI interaction notes
- Update docs/USER_MANUAL.md: Add step-by-step export instructions

Step 3: Test export functionality
- Execute test command
- Wait for confirmation: "Test passed"
- Update agents.md: Add to Testing section
- Document any edge cases found in docs/DEVELOPER_GUIDE.md
```

### Documentation Checkpoints

Before proceeding to the next change, verify:

- [ ] All affected documentation files identified
- [ ] Version numbers updated where applicable
- [ ] Examples provided for new features
- [ ] Cross-references added between documentation files
- [ ] Troubleshooting sections updated if needed
- [ ] User-facing changes reflected in USER_MANUAL.md
- [ ] Technical details captured in DEVELOPER_GUIDE.md
- [ ] System overview updated in agents.md

---

## Formatting Standards & Templates

### Documentation File Standards

#### agents.md Formatting
- **Version Entries**: Single line format with date
- **Feature Summaries**: Bullet points with clear descriptions
- **Code Examples**: Use triple backticks with language identifier
- **File Paths**: Always use markdown links: `` [`filename`](path/to/file.ext:line) ``

#### DEVELOPER_GUIDE.md Formatting
- **API Endpoints**: Include method, path, parameters, request/response examples
- **Code Examples**: Full, executable code snippets
- **Troubleshooting**: Problem → Cause → Solution → Prevention format

#### USER_MANUAL.md Formatting
- **Instructions**: Numbered steps for sequential actions
- **Screenshots**: Placeholder text indicating where images should be
- **Warnings**: Use bold **Problem** and **Solution** headers

### Templates for Common Documentation Types

#### Template 1: New Feature Documentation

**agents.md Entry:**
```markdown
## Complete Feature Summary

### Feature Name (vX.X.X)
- **Description**: Brief one-line description
- **Implementation**: Key technical details
- **Benefits**: User-facing improvements
- **Files Changed**: [`file1.php`](api/file1.php:1), [`file2.js`](assets/js/file2.js:1)
```

**DEVELOPER_GUIDE.md Entry:**
```markdown
### Feature Name

#### Technical Implementation
Description of how the feature works internally.

#### API Usage
```javascript
// Example code
const result = await fetch('/api/endpoint');
```

#### Configuration
Required settings in [`config/wp-pos-config.json`](../config/wp-pos-config.json:1)

#### Testing
```bash
php tests/php/test-feature.php
```
```

**USER_MANUAL.md Entry:**
```markdown
### Using Feature Name

1. Navigate to the relevant section
2. Click the "Feature" button
3. Configure your preferences
4. Click "Save" to apply changes

**Note**: This feature requires [prerequisite] to be enabled.
```

#### Template 2: Bug Fix Documentation

**agents.md Entry:**
```markdown
- v1.8.XX: Fixed [issue description] - [root cause] now [resolution]
```

**DEVELOPER_GUIDE.md Entry:**
```markdown
#### Issue Title (vX.X.XX)
- **Problem**: Clear description of the bug and symptoms
- **Root Cause**: Technical explanation of what caused the issue
- **Solution**: What was changed to fix it
  - Changed [`file.php`](../api/file.php:45) line 45 from X to Y
  - Updated [`logic.js`](../assets/js/logic.js:123) to handle edge case
- **Prevention**: How to avoid similar issues in the future
- **Testing**: How to verify the fix works
```

**USER_MANUAL.md Entry (Troubleshooting):**
```markdown
#### Issue Name
- **Problem**: User-friendly description of the issue
- **Solution**: Step-by-step fix instructions
- **Check**: How to verify the problem is resolved
```

#### Template 3: API Endpoint Documentation

**DEVELOPER_GUIDE.md Entry:**
```markdown
#### [METHOD] /api/endpoint-name.php

[Brief description of what this endpoint does]

**Query Parameters:**
- `param1`: (required) Description of parameter
- `param2`: (optional) Description of parameter, default: value

**Request Body:**
```json
{
    "field1": "value1",
    "field2": 123
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "result": "value"
    }
}
```

**Error Responses:**
```json
{
    "success": false,
    "error": "Error message",
    "code": "ERROR_CODE"
}
```

**Example Usage:**
```javascript
const response = await fetch('/api/endpoint-name.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ field1: 'value1', field2: 123 })
});
```

**Related Functions:**
- See [`functionName()`](../api/file.php:123) for implementation
- Used by [`UIComponent`](../assets/js/component.js:45)
```

#### Template 4: Configuration Change Documentation

**agents.md Entry:**
```markdown
- v1.8.XX: Updated configuration - added `new.setting` for [purpose]
```

**DEVELOPER_GUIDE.md Entry:**
```markdown
### Configuration: new.setting

**Purpose**: Detailed explanation of what this setting controls

**Type**: string | number | boolean | array | object

**Default Value**: `default_value`

**Example:**
```json
{
    "category": {
        "new_setting": "example_value"
    }
}
```

**Impact**: What happens when this setting is changed

**Related Code**: 
- Implemented in [`file.php`](../api/file.php:78) line 78
- Used by [`component.js`](../assets/js/component.js:12)
```

### Markdown Link Format

**ALWAYS use this format for referencing code:**
```markdown
[`filename.ext`](relative/path/to/filename.ext:line_number)
[`functionName()`](relative/path/to/file.ext:line_number)
[`ClassName`](relative/path/to/file.ext:line_number)
[`$variable`](relative/path/to/file.ext:line_number)
```

**Examples:**
- Configuration file: [`wp-pos-config.json`](config/wp-pos-config.json:1)
- API endpoint: [`products.php`](api/products.php:1)
- Function reference: [`fetchProducts()`](assets/js/main.js:234)
- Class reference: [`WP-POS_Cache_Manager`](api/cache-manager.php:15)

### Version Number Format

- **Major.Minor.Patch**: `1.8.17`
- **Major**: Breaking changes or complete rewrites
- **Minor**: New features, significant improvements
- **Patch**: Bug fixes, minor updates

Always increment the appropriate version number when documenting changes.

---

## Documentation Detail Requirements

### Change Classification

#### Major Features (New Functionality)
**Required Documentation:**
- **agents.md**: Version entry, feature summary section, update file structure if needed
- **DEVELOPER_GUIDE.md**: Full section with architecture, API reference, code examples, testing instructions
- **USER_MANUAL.md**: Complete usage guide with numbered steps, examples, troubleshooting

**Detail Level:** Comprehensive
- Minimum 3-5 paragraphs of explanation
- At least 2 code examples
- Full API documentation if applicable
- User workflow documentation
- Edge cases and error handling

**Example:** Adding product export functionality (v1.7.5)

#### Minor Features (Enhancements)
**Required Documentation:**
- **agents.md**: Version entry, update feature summary
- **DEVELOPER_GUIDE.md**: Brief section or addition to existing section
- **USER_MANUAL.md**: Update relevant section with new information

**Detail Level:** Moderate
- 1-2 paragraphs of explanation
- At least 1 code example
- Brief usage instructions

**Example:** Adding keyboard shortcuts to product search (v1.6.8)

#### Bug Fixes (Corrections)
**Required Documentation:**
- **agents.md**: Version entry describing fix
- **DEVELOPER_GUIDE.md**: Add to troubleshooting section
- **USER_MANUAL.md**: Update troubleshooting if user-facing

**Detail Level:** Specific
- Problem description
- Root cause explanation
- Solution implemented
- Prevention guidelines

**Example:** Fixed attribute options disappearing (v1.6.6)

#### Configuration Changes
**Required Documentation:**
- **agents.md**: Version entry
- **DEVELOPER_GUIDE.md**: Update configuration section with new options
- **USER_MANUAL.md**: Update settings section if user-configurable

**Detail Level:** Precise
- Setting name and location
- Purpose and impact
- Default value
- Valid options

**Example:** Added `max_cache_size` to config (v1.4.0)

#### Performance Optimizations
**Required Documentation:**
- **agents.md**: Version entry with metrics (before/after)
- **DEVELOPER_GUIDE.md**: Technical implementation details
- **USER_MANUAL.md**: User-visible improvements if applicable

**Detail Level:** Quantitative
- Specific performance metrics
- Implementation approach
- Benchmarking methodology

**Example:** Reduced product load time from 3.2s to 1.5s (v1.5.0)

### Decision Documentation Requirements

Document **important development decisions**, not just code changes:

#### Architecture Decisions
- Why a particular pattern was chosen over alternatives
- Trade-offs considered
- Long-term implications

**Example:**
```markdown
### State Management Decision (v1.5.0)

**Decision**: Migrated from global variables to centralized `appState` object

**Rationale**: 
- Eliminated ReferenceError issues
- Improved debugging capability
- Enabled better testing
- Reduced naming conflicts

**Trade-offs**: 
- Required refactoring 2,389 lines of code
- Breaking change for any external integrations
- Learning curve for developers

**Alternative Considered**: Vuex state management
- Rejected due to: Additional dependency, increased bundle size, complexity
```

#### Technology Choices
- Why a library/framework was chosen or avoided
- Compatibility considerations
- Future maintenance implications

#### Security Decisions
- Security measures implemented
- Risk assessment
- Compliance requirements

### Before/After State Documentation

For modifications to existing functionality, always document:

#### Code Changes
```markdown
**Before:**
```javascript
// Old implementation
function fetchProducts() {
    return fetch('/api/products.php');
}
```

**After:**
```javascript
// New implementation with error handling
async function fetchProducts() {
    try {
        const response = await fetch('/api/products.php');
        if (!response.ok) throw new Error('Fetch failed');
        return await response.json();
    } catch (error) {
        console.error('Product fetch error:', error);
        return { success: false, error: error.message };
    }
}
```

**Changes:**
- Added async/await for better error handling
- Added try-catch block
- Added response validation
- Returns structured error object
```

#### Performance Changes
```markdown
**Before:**
- Load time: 3.2 seconds
- Memory usage: 78MB
- Cache hit rate: 45%

**After:**
- Load time: 1.5 seconds (53% improvement)
- Memory usage: 52MB (33% reduction)
- Cache hit rate: 95% (111% improvement)

**Implementation:** Added [`WP-POS_Cache_Manager`](api/cache-manager.php:1) with 5-minute TTL
```

#### UI Changes
```markdown
**Before:**
- Products displayed in single long list
- No pagination
- All 737 products loaded at once

**After:**
- Products displayed in paginated grid
- 20 products per page
- Load more functionality
- 80% faster initial load time

**User Impact:** Significantly improved browsing experience, especially on slower connections
```

---

## Concrete Examples

### Example 1: Feature Implementation with Proper Documentation

**Scenario:** Adding a product search autocomplete feature

#### Step 1: Implement Backend API
```bash
# Create new API endpoint
write_to_file api/product-autocomplete.php
```

**Immediately After Tool Success:**

Update [`agents.md`](agents.md:1):
```markdown
- v1.8.18: Implemented product search autocomplete with real-time suggestions
```

Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1):
```markdown
#### GET /api/product-autocomplete.php

Returns product suggestions based on partial search input.

**Query Parameters:**
- `query`: (required) Search term (minimum 2 characters)
- `limit`: (optional) Maximum suggestions to return, default: 10

**Response:**
```json
{
    "success": true,
    "data": {
        "suggestions": [
            {"id": 123, "name": "Product Name", "sku": "SKU123"},
            {"id": 124, "name": "Another Product", "sku": "SKU124"}
        ],
        "count": 2
    }
}
```
```

#### Step 2: Implement Frontend Component
```bash
# Modify main JavaScript
apply_diff assets/js/main.js
```

**Immediately After Tool Success:**

Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1):
```markdown
### Product Search Autocomplete

**Implementation:**
```javascript
// Implemented in [`main.js`](../assets/js/main.js:567)
async function handleSearchAutocomplete(query) {
    if (query.length < 2) return [];
    
    const response = await fetch(`/api/product-autocomplete.php?query=${query}`);
    const data = await response.json();
    
    return data.success ? data.data.suggestions : [];
}
```

**Usage:** Type at least 2 characters in product search to see suggestions
```

Update [`USER_MANUAL.md`](docs/USER_MANUAL.md:1):
```markdown
### Product Search Autocomplete (New in v1.8.18)

As you type in the product search box, you'll see real-time suggestions:

1. Type at least 2 characters in the search box
2. Suggestions appear automatically below the search input
3. Click any suggestion to select that product
4. Press ESC to close suggestions without selecting

**Note:** Suggestions are based on product names and SKUs.
```

#### Step 3: Test Implementation
```bash
# Run tests
execute_command php tests/php/test-autocomplete.php
```

**After Test Success:**

Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1):
```markdown
### Testing Product Autocomplete

```bash
php tests/php/test-autocomplete.php
```

**Test Coverage:**
- Empty query handling
- Single character rejection
- Valid query suggestions
- Result limit enforcement
- Special character handling
```

### Example 2: Bug Fix with Incremental Documentation

**Scenario:** Fixing cart total calculation error

#### Step 1: Identify and Document Problem
**Before Fixing:**

Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1) Troubleshooting:
```markdown
#### Cart Total Calculation Error (v1.8.18)
- **Problem**: Cart displays incorrect total when discount codes are applied
- **Symptoms**: Total shows higher amount than expected, ignores percentage discounts
- **Root Cause**: INVESTIGATING...
```

#### Step 2: Fix the Code
```bash
# Fix the calculation function
apply_diff assets/js/modules/cart.js
```

**Immediately After Tool Success:**

Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1):
```markdown
#### Cart Total Calculation Error (v1.8.18)
- **Problem**: Cart displays incorrect total when discount codes are applied
- **Symptoms**: Total shows higher amount than expected, ignores percentage discounts
- **Root Cause**: Discount percentage was being added instead of subtracted in [`calculateCartTotal()`](../assets/js/modules/cart.js:234)
- **Solution**: Fixed line 234 from `total += discount` to `total -= discount`
- **Prevention**: Added unit tests for discount calculations in [`test-cart.js`](../tests/js/test-cart.js:1)

**Before:**
```javascript
function calculateCartTotal() {
    let total = subtotal;
    if (discount > 0) total += discount; // BUG: Should subtract
    return total;
}
```

**After:**
```javascript
function calculateCartTotal() {
    let total = subtotal;
    if (discount > 0) total -= discount; // FIXED: Now correctly subtracts
    return total;
}
```
```

Update [`agents.md`](agents.md:1):
```markdown
- v1.8.18: Fixed cart total calculation bug - discount percentages now correctly subtracted instead of added
```

#### Step 3: Add Test
```bash
# Create test for the fix
write_to_file tests/js/test-cart-discount.js
```

**Immediately After Tool Success:**

Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1):
```markdown
### Testing Cart Calculations

```bash
# Run cart tests
node tests/js/test-runner.js test-cart-discount.js
```

**Test Cases:**
- Percentage discount: 10% off $100 = $90
- Fixed amount discount: $10 off $100 = $90
- Multiple discounts: Correctly applies both
- Edge case: 100% discount = $0
```

### Example 3: Configuration Change Documentation

**Scenario:** Adding new cache configuration option

#### Step 1: Update Configuration File
```bash
# Modify config
apply_diff config/wp-pos-config.json
```

**Immediately After Tool Success:**

Update [`agents.md`](agents.md:1):
```markdown
- v1.8.19: Fixed things
- v1.8.20: Made improvements
```

**Why Wrong:** No actionable information, no searchability, no learning value

#### ✅ CORRECT: Descriptive Version History
```markdown
- v1.8.18: Implemented product search autocomplete - real-time suggestions appear after typing 2+ characters, using new [`/api/product-autocomplete.php`](api/product-autocomplete.php:1) endpoint
- v1.8.19: Fixed cart total calculation in [`calculateCartTotal()`](assets/js/modules/cart.js:234) - discount percentages now correctly subtracted (was adding), added unit tests
- v1.8.20: Added `cache.cleanup_on_startup` boolean to [`wp-pos-config.json`](config/wp-pos-config.json:1) - controls automatic cache cleanup at application start, defaults to true
```

#### ❌ WRONG: No Cross-References
```markdown
### New Feature

Added product export functionality. It's in the API somewhere and there's a button for it.
```

**Why Wrong:** No file references, no line numbers, impossible to locate in codebase

#### ✅ CORRECT: Complete Cross-References
```markdown
### Product Export (v1.8.18)

**Implementation:**
- API Endpoint: [`/api/export-products.php`](api/export-products.php:1)
- Frontend Handler: [`handleProductExport()`](assets/js/main.js:789)
- UI Button: Added to products page at [`index.php`](index.php:456) line 456
- Configuration: Uses `export.format` from [`wp-pos-config.json`](config/wp-pos-config.json:23)

**Related Documentation:**
- Technical details: [DEVELOPER_GUIDE.md - Export API](docs/DEVELOPER_GUIDE.md#export-api)
- User guide: [USER_MANUAL.md - Exporting Products](docs/USER_MANUAL.md#exporting-products)
```

---

## Version Management & Cache Busting Protocol

### ⚠️ CRITICAL: Version Updates are MANDATORY

**YOU MUST UPDATE VERSION NUMBERS AFTER EVERY CHANGE THAT AFFECTS CLIENT-SIDE FILES.**

Failing to update versions will cause:
- ❌ Users seeing cached old code even after updates
- ❌ JavaScript errors from mismatched file versions
- ❌ Features not working in production
- ❌ Cache-related bugs that are extremely difficult to debug

**This is NOT optional. Version updates are as important as the code changes themselves.**

---

### Current Versioning System

WP POS uses **query parameter versioning** for cache busting:

**How It Works:**
```html
<!-- Version parameters force browser to download new files -->
<script src="assets/js/modules/routing.js?v=1.5.10"></script>
<script src="assets/js/main.js?v=1.8.17"></script>
```

**Why This Matters:**
- Browsers cache JavaScript files aggressively
- Without version changes, users get old cached code
- Version parameter change = browser treats it as a new file
- Forces fresh download = users always get latest code

**Current Versions (as of latest update):**
- System Version: `v1.8.26` (in [`agents.md`](agents.md:1792))
- Main JavaScript: `v1.8.26` (in [`index.php`](index.php:23))
- Routing Module: `v1.5.11` (in [`index.php`](index.php:21))

---

### When to Update Versions

#### Major Version (X.0.0) - Breaking Changes
**Update when:**
- Complete system rewrites or major architecture changes
- Breaking API changes that affect all consumers
- Removal of deprecated features that break existing functionality
- Database schema changes requiring migrations
- Changes to core authentication or security systems

**Examples:**
- `1.8.17` → `2.0.0`: Complete rewrite from jQuery to vanilla JavaScript
- `2.0.0` → `3.0.0`: Migration from REST API to GraphQL

**Impact:** Requires user action, may need data migration, extensive testing needed

---

#### Minor Version (0.X.0) - New Features
**Update when:**
- Adding new features or significant enhancements
- New API endpoints or major functionality additions
- UI/UX improvements that change user workflows
- Performance optimizations with measurable impact
- New configuration options or settings

**Examples:**
- `1.8.17` → `1.9.0`: Added comprehensive reporting system with charts
- `1.6.5` → `1.7.0`: Implemented product search autocomplete

**Impact:** Backward compatible, enhances functionality, no breaking changes

---

#### Patch Version (0.0.X) - Bug Fixes & Minor Updates
**Update when:**
- Bug fixes that don't change functionality
- Minor styling or layout adjustments
- Performance tweaks without major impact
- Documentation updates (code comments, inline docs)
- Dependency updates that don't change behavior
- Security patches

**Examples:**
- `1.8.17` → `1.8.18`: Fixed cart total calculation bug
- `1.8.18` → `1.8.19`: Updated button styling for consistency

**Impact:** Backward compatible, fixes issues, minimal risk

---

### How to Update Versions

#### Step 1: Determine Version Increment Type

**Ask yourself:**
- Does this break existing functionality? → **Major**
- Does this add new features? → **Minor**
- Does this fix bugs or make minor changes? → **Patch**

#### Step 2: Update Version in index.php

**Location:** [`index.php`](index.php:21-23)

**For JavaScript file changes:**
```html
<!-- BEFORE -->
<script src="assets/js/main.js?v=1.8.17"></script>

<!-- AFTER (for patch) -->
<script src="assets/js/main.js?v=1.8.18"></script>
```

**For routing module changes:**
```html
<!-- BEFORE -->
<script src="assets/js/modules/routing.js?v=1.5.10"></script>

<!-- AFTER (for patch) -->
<script src="assets/js/modules/routing.js?v=1.5.11"></script>
```

**⚠️ IMPORTANT:**
- Update ONLY the file(s) you modified
- If you change `main.js`, update `main.js` version
- If you change `routing.js`, update `routing.js` version
- If you change both, update both versions

#### Step 3: Update System Version in agents.md

**Location:** [`agents.md`](agents.md:1290) (System Overview section)

**Update the "Current System Status" section:**
```markdown
<!-- BEFORE -->
**Version**: 1.8.18

<!-- AFTER -->
**Version**: 1.8.19
```

**Update the "Latest Update" line:**
```markdown
<!-- BEFORE -->
**Latest Update**: WP POS v1.8.18 - [previous feature description]

<!-- AFTER -->
**Latest Update**: WP POS v1.8.19 - [your new feature/fix description]
```

#### Step 4: Add Version History Entry

**Location:** [`agents.md`](agents.md:1823) (bottom of file)

**Add new line to Version History section:**
```markdown
## Version History

- v1.8.19: [Brief description] - [technical details with file links]
- v1.8.18: Implemented comprehensive reporting system - added [`/api/reports.php`]...
```

**Format:**
```markdown
- v1.X.X: [What changed] - [Why/How with file references]
```

#### Step 5: Update DEVELOPER_GUIDE.md (If Applicable)

**Location:** [`docs/DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1)

**Add version reference in relevant section:**
```markdown
### Feature Name (v1.8.19)
[Feature documentation with version number]
```

---

### Cache Busting Mechanism Explained

#### How Browser Caching Works
```
User visits site
    ↓
Browser checks: "Do I have main.js?v=1.8.17 cached?"
    ↓
YES → Use cached version (FAST but may be outdated)
NO  → Download from server (SLOW but always current)
```

#### How Version Changes Force Updates
```
You deploy: main.js?v=1.8.18 (version changed)
    ↓
User visits site
    ↓
Browser checks: "Do I have main.js?v=1.8.18 cached?"
    ↓
NO (because v1.8.18 is NEW) → Downloads fresh version
    ↓
User gets latest code! ✅
```

#### What Happens Without Version Updates
```
You deploy: main.js (code updated, but version still v1.8.17)
    ↓
User visits site
    ↓
Browser checks: "Do I have main.js?v=1.8.17 cached?"
    ↓
YES (same version!) → Uses OLD cached code
    ↓
User gets outdated code! ❌
Result: New features don't work, bugs aren't fixed
```

---

### Version Update Checklist

**Before making ANY code change to client-side files, verify:**

- [ ] I understand what type of change this is (major/minor/patch)
- [ ] I know which version number to increment
- [ ] I know which files I'm modifying

**After successful code change, immediately:**

- [ ] Update version in [`index.php`](index.php:21-23) for affected file(s)
- [ ] Update system version in [`agents.md`](agents.md:1290) System Overview
- [ ] Update "Latest Update" line in [`agents.md`](agents.md:1290)
- [ ] Add entry to Version History in [`agents.md`](agents.md:1823)
- [ ] Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1) if applicable
- [ ] Test that cache busting works (hard refresh shows new version)
- [ ] Document the version change in commit message

**Verification:**

- [ ] All version numbers are consistent
- [ ] No duplicate version numbers in history
- [ ] Version follows semantic versioning rules
- [ ] Documentation references correct version numbers

---

### Examples

#### Example 1: Bug Fix (Patch Version)

**Scenario:** Fixed cart total calculation error

**Version Update:**
```html
<!-- index.php line 23 -->
<!-- BEFORE -->
<script src="assets/js/main.js?v=1.8.17"></script>

<!-- AFTER -->
<script src="assets/js/main.js?v=1.8.18"></script>
```

**agents.md System Overview (line 1290):**
```markdown
**Version**: 1.8.18
**Latest Update**: WP POS v1.8.18 - Fixed cart total calculation bug where discount percentages were added instead of subtracted
```

**agents.md Version History (line 1823):**
```markdown
- v1.8.18: Fixed cart total calculation in [`calculateCartTotal()`](assets/js/modules/cart.js:234) - discount percentages now correctly subtracted (was adding), added unit tests
```

---

#### Example 2: New Feature (Minor Version)

**Scenario:** Added product search autocomplete

**Version Update:**
```html
<!-- index.php line 23 -->
<!-- BEFORE -->
<script src="assets/js/main.js?v=1.8.17"></script>

<!-- AFTER -->
<script src="assets/js/main.js?v=1.9.0"></script>
```

**agents.md System Overview (line 1290):**
```markdown
**Version**: 1.9.0
**Latest Update**: WP POS v1.9.0 - Implemented product search autocomplete with real-time suggestions appearing after 2+ characters
```

**agents.md Version History (line 1823):**
```markdown
- v1.9.0: Implemented product search autocomplete - real-time suggestions appear after typing 2+ characters, using new [`/api/product-autocomplete.php`](api/product-autocomplete.php:1) endpoint with [`handleSearchAutocomplete()`](assets/js/main.js:567) frontend handler
```

---

#### Example 3: Multiple File Changes

**Scenario:** Updated both main.js and routing.js

**Version Update:**
```html
<!-- index.php lines 21-23 -->
<!-- BEFORE -->
<script src="assets/js/modules/routing.js?v=1.5.10"></script>
<script src="assets/js/main.js?v=1.8.17"></script>

<!-- AFTER -->
<script src="assets/js/modules/routing.js?v=1.5.11"></script>
<script src="assets/js/main.js?v=1.8.18"></script>
```

**agents.md Update:**
```markdown
**Version**: 1.8.18
**Latest Update**: WP POS v1.8.18 - Enhanced routing system with improved error handling and updated main navigation logic

## Version History
- v1.8.18: Enhanced routing system - improved error handling in [`routing.js`](assets/js/modules/routing.js:1) and updated navigation logic in [`main.js`](assets/js/main.js:1)
```

---

#### Example 4: Version Number in URLs

**How versions appear in browser:**
```
https://example.com/wp-pos/assets/js/main.js?v=1.8.18
                                                  ↑
                                        Cache busting parameter
```

**Browser treats these as DIFFERENT files:**
- `main.js?v=1.8.17` ← Old version (cached)
- `main.js?v=1.8.18` ← New version (forces download)

---

### Integration with Existing Documentation Protocols

#### During Real-Time Documentation (Section 3)

**Add version update as a documentation step:**
```
1. Make code change to main.js
2. ✅ Confirm tool execution succeeded
3. 📝 UPDATE VERSION: Increment main.js version in index.php
4. 📝 DOCUMENT: Update agents.md version history
5. 📝 DOCUMENT: Add to DEVELOPER_GUIDE.md with version reference
```

#### In Documentation Templates (Section 4)

**Update "New Feature" template to include:**
```markdown
**agents.md Entry:**
- v1.X.X: [Feature name] - [Description] with file links
- **Files Changed**: [`file.js`](path/file.js:1) (v1.X.X)
- **Version Update**: Updated from v1.X.X to v1.X.X in [`index.php`](index.php:23)
```

#### In Interconnected Changes (Section 7)

**Add to Phase 6: Final Documentation Pass:**
```markdown
#### Phase 6: Version Update & Final Documentation
- [ ] Version numbers updated in index.php
- [ ] System version updated in agents.md
- [ ] Version history entry added
- [ ] All version references consistent
- [ ] [Continue with existing cross-reference checks...]
```

---

### Common Version Update Mistakes

#### ❌ MISTAKE 1: Not updating version at all
```html
<!-- You changed main.js but didn't update version -->
<script src="assets/js/main.js?v=1.8.17"></script>
```
**Result:** Users get cached old code, new features don't work

---

#### ❌ MISTAKE 2: Wrong version increment
```html
<!-- Bug fix but incremented major version -->
<script src="assets/js/main.js?v=2.0.0"></script>
```
**Result:** Confusing version history, breaks semantic versioning

---

#### ❌ MISTAKE 3: Updating wrong file version
```html
<!-- You changed main.js but updated routing.js version -->
<script src="assets/js/modules/routing.js?v=1.5.11"></script>
<script src="assets/js/main.js?v=1.8.17"></script> <!-- Should be 1.8.18! -->
```
**Result:** Browser doesn't re-download the changed file

---

#### ❌ MISTAKE 4: Inconsistent version numbers
```markdown
<!-- index.php says v1.8.18 but agents.md says v1.8.19 -->
```
**Result:** Documentation doesn't match deployed version

---

#### ❌ MISTAKE 5: Skipping version history entry
```markdown
<!-- Updated version in index.php but no history entry in agents.md -->
```
**Result:** No record of what changed, impossible to track features

---

### Version Update Quick Reference

| Change Type | Example | Version Change | Update Files |
|-------------|---------|----------------|--------------|
| **Bug Fix** | Fixed calculation error | 1.8.17 → 1.8.18 | index.php, agents.md |
| **New Feature** | Added autocomplete | 1.8.17 → 1.9.0 | index.php, agents.md, DEVELOPER_GUIDE.md |
| **Breaking Change** | Complete rewrite | 1.8.17 → 2.0.0 | index.php, agents.md, DEVELOPER_GUIDE.md, USER_MANUAL.md |
| **Minor Update** | Updated styling | 1.8.17 → 1.8.18 | index.php, agents.md |
| **Module Change** | Fixed routing bug | routing: 1.5.10 → 1.5.11 | index.php, agents.md |

---

### Testing Version Updates

#### Manual Verification

1. **Clear browser cache** (Ctrl+Shift+Delete)
2. **Hard refresh** (Ctrl+F5)
3. **Check Network tab** in DevTools
4. **Verify** new version parameter in requests:
   ```
   Request URL: .../main.js?v=1.8.18
   Status: 200 (from server, not cache)
   ```

#### Automated Testing

Add version verification to your test suite:
```javascript
// Check that version was updated
test('Version updated after changes', () => {
    const scriptTag = document.querySelector('script[src*="main.js"]');
    const version = scriptTag.src.match(/v=(\d+\.\d+\.\d+)/)[1];
    
    // Verify version is greater than previous
    assert(version === '1.8.18', 'Version should be updated');
});
```

---

### Emergency Version Rollback

**If you need to rollback to a previous version:**

1. **Revert code changes** to previous version
2. **DO NOT revert version number** - increment forward instead
3. **Document the rollback:**
   ```markdown
   - v1.8.19: Rolled back autocomplete feature from v1.8.18 due to performance issues
   ```
4. **Add explanation** in DEVELOPER_GUIDE.md troubleshooting section

**Why not revert version numbers?**
- Users may have cached the "bad" version
- Going backward (1.8.18 → 1.8.17) won't force cache refresh
- Always increment forward (1.8.18 → 1.8.19) to ensure fresh download

---

### Summary: Version Management Rules

1. **Always update versions** when changing client-side code
2. **Update immediately** after confirming code change success
3. **Be consistent** across index.php and documentation
4. **Follow semantic versioning** (major.minor.patch)
5. **Document the change** in version history
6. **Test cache busting** works after update
7. **Never skip versions** - increment sequentially
8. **Never revert versions** - always move forward

**Remember: Version updates are not optional. They are critical for production deployments.**

---

## Interconnected Changes Protocol

### Understanding Interconnected Changes

Many changes in this codebase affect multiple components. When making interconnected changes, you must maintain consistency across all affected files and documentation.

#### Common Interconnection Patterns

1. **API + Frontend + Documentation**
   - API endpoint change → Frontend consuming code → User documentation
   - Example: Changing product API response format

2. **Configuration + Implementation + Documentation**
   - Config file → Code reading config → Developer guide → User manual
   - Example: Adding new cache setting

3. **Database + API + Frontend**
   - Schema change → API query → Frontend display
   - Example: Adding new product field

4. **State Management + Components**
   - appState structure → Multiple components using state
   - Example: Adding new cart property

### Protocol for Interconnected Changes

#### Step 1: Identify All Affected Components

Before making ANY change, list all affected components:

**Example: Adding product "favorite" functionality**
```
Affected Components:
- Database: wp_postmeta (new meta field)
- API: api/products.php (return favorite status)
- Frontend: assets/js/modules/products.js (display star icon)
- State: assets/js/modules/state.js (track favorites)
- UI: index.php (favorite button)
- Config: config/wp-pos-config.json (favorite settings)
- Docs: All three documentation files
```

#### Step 2: Plan Change Sequence

Order changes to maintain system functionality:

```
1. Database/Backend first (foundation)
2. API layer (interface)
3. Frontend/State (consumption)
4. UI (presentation)
5. Documentation (explanation)
```

#### Step 3: Implement with Incremental Documentation

For each component change:
1. Make the change
2. Confirm success
3. Document immediately (all relevant docs)
4. Verify related components still compatible
5. Move to next component

#### Step 4: Cross-Reference Updates

After all changes complete, verify all cross-references:

**Checklist:**
- [ ] agents.md updated with version entry
- [ ] agents.md features list updated
- [ ] agents.md file structure updated (if new files)
- [ ] DEVELOPER_GUIDE.md API section updated (if API changed)
- [ ] DEVELOPER_GUIDE.md architecture updated (if structure changed)
- [ ] DEVELOPER_GUIDE.md troubleshooting added (for new issues)
- [ ] USER_MANUAL.md usage instructions added (if user-facing)
- [ ] USER_MANUAL.md troubleshooting updated (for user issues)
- [ ] All code files have proper comments referencing docs
- [ ] All docs have proper markdown links to code files

### Example: Complete Interconnected Change

**Task:** Add product rating system

#### Phase 1: Database Layer
```bash
# Modify database schema
execute_command wp post meta add ...
```

**Document Immediately:**
- agents.md: Add to version history
- DEVELOPER_GUIDE.md: Add to Database Schema section

#### Phase 2: API Layer
```bash
# Update API to return ratings
apply_diff api/products.php
```

**Document Immediately:**
- agents.md: Update file description
- DEVELOPER_GUIDE.md: Update API Reference with new response field
  ```markdown
  **Response includes new field:**
  ```json
  {
      "rating": 4.5,
      "rating_count": 28
  }
  ```
  ```

#### Phase 3: State Management
```bash
# Add rating to appState
apply_diff assets/js/modules/state.js
```

**Document Immediately:**
- DEVELOPER_GUIDE.md: Update State Management section
  ```markdown
  ### appState Structure
  ```javascript
  appState.products = {
      all: [],
      current: null,
      rating: null  // NEW: Current product rating
  };
  ```
  ```

#### Phase 4: Frontend Display
```bash
# Add star rating display
apply_diff assets/js/modules/products.js
```

**Document Immediately:**
- DEVELOPER_GUIDE.md: Add UI implementation notes
- USER_MANUAL.md: Add to Product Information section

#### Phase 5: Configuration
```bash
# Add rating settings
apply_diff config/wp-pos-config.json
```

**Document Immediately:**
- DEVELOPER_GUIDE.md: Add configuration documentation
- agents.md: Update configuration summary

#### Phase 6: Final Documentation Pass

**Verify all cross-references:**
```markdown
<!-- agents.md -->
- v1.9.0: Implemented product rating system with 5-star display, average calculation, and user review counting

<!-- DEVELOPER_GUIDE.md -->
### Product Rating System (v1.9.0)
- API returns rating data in [`/api/products.php`](../api/products.php:1)
- State managed in [`appState.products.rating`](../assets/js/modules/state.js:45)
- Display handled by [`renderProductRating()`](../assets/js/modules/products.js:234)
- Configuration in [`config/wp-pos-config.json`](../config/wp-pos-config.json:67)

<!-- USER_MANUAL.md -->
### Product Ratings (New in v1.9.0)
Each product displays its average rating based on customer reviews:
1. Look for the star rating below the product name
2. See the number of reviews in parentheses
3. Click the rating to see detailed reviews
For technical implementation, see [DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md#product-rating-system)
```

### Maintaining Documentation Consistency

#### Consistency Checklist

When making interconnected changes, verify:

1. **Terminology Consistency**
   - [ ] Same feature name used across all docs
   - [ ] Same variable/function names referenced consistently
   - [ ] Same technical terms (no synonyms causing confusion)

2. **Version Consistency**
   - [ ] All docs show same version number for feature
   - [ ] Version history matches across files
   - [ ] Dates are consistent

3. **Link Consistency**
   - [ ] All code links point to correct files and lines
   - [ ] All doc cross-references are bidirectional
   - [ ] No broken links

4. **Example Consistency**
   - [ ] Code examples match actual implementation
   - [ ] API examples show current response format
   - [ ] User instructions match current UI

5. **Status Consistency**
   - [ ] Feature marked as "New in vX.X.X" in all relevant places
   - [ ] Deprecated features marked consistently
   - [ ] Removed features deleted from all docs

### Emergency Rollback Documentation

If you need to rollback changes:

1. **Document the rollback as a new version:**
   ```markdown
   - v1.9.1: Rolled back product rating system (v1.9.0) due to performance issues - will be reimplemented in v1.9.5
   ```

2. **Update all affected documentation:**
   - Mark feature as "Temporarily Removed"
   - Explain reason for rollback
   - Indicate timeline for re-implementation

3. **Maintain historical record:**
   - Do NOT delete the original documentation
   - Add rollback notes to original entries
   - Keep implementation details for future reference

---

## Summary

### Key Principles for AI Agents

1. **Read First**: Always read relevant files before making changes
2. **Document Immediately**: After each successful change, not at the end
3. **Cross-Reference Everything**: Link code to docs and docs to code
4. **Be Specific**: Use concrete examples, file paths, and line numbers
5. **Think Interconnected**: Consider all affected components
6. **Maintain Consistency**: Same terms, same versions, same links
7. **Test Before Completing**: Verify changes work before marking done
8. **Version Appropriately**: Increment version numbers correctly

### Daily Workflow Checklist

Before starting work:
- [ ] Read task requirements completely
- [ ] Read current [`agents.md`](agents.md:1) system overview
- [ ] Read relevant sections of [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1)
- [ ] Check [`USER_MANUAL.md`](docs/USER_MANUAL.md:1) if user-facing
- [ ] Identify all files that will be affected

During work:
- [ ] Make one change at a time
- [ ] Wait for confirmation of success
- [ ] Document immediately in all relevant files
- [ ] Use proper markdown formatting with links
- [ ] Cross-reference between documentation files
- [ ] Test the change before proceeding

After work:
- [ ] Verify all documentation updated
- [ ] Check all cross-references valid
- [ ] Ensure version numbers consistent
- [ ] Confirm no broken links
- [ ] Review for terminology consistency

### Documentation Quick Reference

| When to Update | agents.md | DEVELOPER_GUIDE.md | USER_MANUAL.md |
|----------------|-----------|-------------------|----------------|
| New feature | Version entry, feature summary | Architecture, API, examples | Usage instructions |
| Bug fix | Version entry | Troubleshooting section | Troubleshooting if user-facing |
| API change | Version entry | API Reference section | N/A unless user-facing |
| Config change | Version entry | Configuration section | Settings section if user-configurable |
| UI change | Version entry | UI implementation notes | Usage instructions |
| Performance optimization | Version entry with metrics | Technical implementation | User-visible improvements |

---

# WP POS (WordPress Point of Sale) - Complete System Documentation

- v1.8.18: Added `cache.cleanup_on_startup` configuration option to control automatic cache cleanup behavior
```

Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1):
```markdown
### Configuration: cache.cleanup_on_startup

**Purpose:** Controls whether cache is automatically cleaned up when the application starts

**Type:** boolean

**Default Value:** `true`

**Location:** [`config/wp-pos-config.json`](../config/wp-pos-config.json:1)

**Example:**
```json
{
    "cache": {
        "enabled": true,
        "default_ttl": 300,
        "cleanup_on_startup": false
    }
}
```

**Impact:**
- `true`: Cache is cleaned on every application start (removes expired entries)
- `false`: Cache cleanup only happens on scheduled intervals

**When to Disable:** 
- High-traffic environments where startup cleanup causes delays
- When using external cache management tools
- During development to preserve cache across restarts

**Related Code:**
- Implemented in [`cache-manager.php`](../api/cache-manager.php:45) line 45
- Called from [`index.php`](../index.php:12) during initialization
```

#### Step 2: Update Implementation
```bash
# Modify cache manager to use new config
apply_diff api/cache-manager.php
```

**Immediately After Tool Success:**

Update [`DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md:1):
```markdown
### Cache Manager Implementation

The [`WP-POS_Cache_Manager`](../api/cache-manager.php:15) class respects the `cleanup_on_startup` configuration:

```php
class WP-POS_Cache_Manager {
    public function __construct() {
        $config = $this->load_config();
        
        if ($config['cache']['cleanup_on_startup'] === true) {
            $this->cleanup_expired();
        }
    }
}
## Version 1.9.151 (2025-10-09)
**Enhanced Attribute Name Selection with Searchable Dropdowns**
- Added searchable dropdown for attribute name selection when adding new attributes
- Fetches available attributes from WooCommerce (both global taxonomy and custom attributes)
- Type-to-search functionality filters attributes in real-time
- Shows attribute type (Global vs Custom) for clarity
- Option to create new custom attribute if not found in suggestions
- Keyboard navigation support (Enter to select, Escape to close)
- Visual feedback with green border on selection
- Consistent UX with variation attribute selectors
- API endpoint `/api/product-edit-simple.php?action=get_available_attributes` provides attribute list

**Technical Implementation:**
- New `setupAttributeNameSearchHandler()` method in [`product-editor.js`](assets/js/modules/products/product-editor.js:510)
- Async fetch of available attributes in [`addAttributeRow()`](assets/js/modules/products/product-editor.js:417)
- Real-time filtering with case-insensitive search
- Stores selected attribute metadata (name, label, type) in data attributes
- Click handlers for both existing attributes and "create new" option

## Version 1.9.34 (2025-10-07)
**Separate Flat/Percentage Values and Proper Formatting**
- Separate value storage for flat vs percentage tabs - switching between tabs preserves both values
- Flat dollar amounts display as "$X.XX", percentages display as "X%"
- Modal always opens with "Flat" tab selected by default
- Switching tabs loads the appropriate saved value for that type
- Cart display shows proper formatting: "-10%" or "-$5.00" based on type
- Only creates fee/discount from the currently active tab type

```

**Configuration-Aware Behavior:** Cache manager checks configuration before performing startup cleanup
```

### Counter-Examples (INCORRECT Approaches)

#### ❌ WRONG: Documenting Multiple Changes at Once
```markdown
<!-- DO NOT DO THIS -->
- v1.8.18: Added autocomplete, fixed cart bug, updated cache config, improved performance, refactored API, updated tests
```

**Why Wrong:** Too vague, no detail, loses track of individual changes

#### ✅ CORRECT: Individual Documentation
```markdown
<!-- DO THIS INSTEAD -->
- v1.8.18: Implemented product search autocomplete with real-time suggestions using new /api/product-autocomplete.php endpoint
- v1.8.19: Fixed cart total calculation bug where discount percentages were added instead of subtracted
- v1.8.20: Added cache.cleanup_on_startup configuration option to control automatic cache cleanup behavior
```

#### ❌ WRONG: Documentation After All Changes
```
1. Create 5 new files
2. Modify 3 existing files
3. Test everything
4. Write all documentation at the end
```

**Why Wrong:** Violates real-time documentation protocol, risks missing details

#### ✅ CORRECT: Documentation Between Each Change
```
1. Create api/endpoint1.php → Document immediately
2. Create api/endpoint2.php → Document immediately
3. Modify assets/js/main.js → Document immediately
4. Test endpoint1 → Document test results
5. Test endpoint2 → Document test results
```

#### ❌ WRONG: Vague Version History
```markdown
- v1.8.18: Updated some stuff
- v1.8.19: Fixed things
# WP POS (WordPress Point of Sale) - Complete System Documentation

## System Overview

WP POS is a modern, enterprise-grade point-of-sale system built on WordPress. The system has been completely refactored and optimized across 4 phases, transforming it from a monolithic application with security vulnerabilities into a secure, performant, and well-documented solution.

## Current System Status

**Status**: ✅ PRODUCTION READY
**Last Updated**: October 7, 2025
**Version**: 1.9.175
**All Phases Completed**: Security, Architecture, Performance, Quality & Monitoring
**Latest Update**: WP POS v1.9.175 - Dynamic Checkout Button Labels - checkout button now displays context-aware text showing exact action and amount: "Issue Refund $195.00" (green), "Complete Exchange & Pay $55.00" (amber), "Complete Exchange" when credit covers all (amber), or "Complete Payment $250.00" (blue) for regular sales

## Architecture

### Frontend Architecture
- **Modular JavaScript**: Split from 2,389-line monolithic file into logical modules
- **State Management**: Centralized `appState` object with validation utilities
- **API Communication**: RESTful endpoints with consistent response formats
- **Bundle Optimization**: 29.38KB minified JavaScript bundle
- **UI Navigation**: Working menu system with smooth animations and overlay close functionality
- **URL Routing**: URL parameter-based routing system for view persistence on page reload

### Backend Architecture
- **WordPress Integration**: Built on WordPress with custom API endpoints
- **Database Layer**: Optimized queries with caching and prepared statements
- **Security**: CSRF protection, input validation, and secure error handling
- **Performance**: File-based caching system with TTL management

## File Structure

```
wp-pos/
├── api/                           # API endpoints
│   ├── auth.php                   # Authentication
│   ├── products.php               # Product management (optimized)
│   ├── product-edit-simple.php    # Comprehensive product editor API
│   ├── orders.php                 # Order processing
│   ├── checkout.php               # Checkout processing
│   ├── reports.php                # Comprehensive reporting with intelligent time granularity
│   ├── settings.php               # Settings management
│   ├── drawer.php                 # Cash drawer management
│   ├── stock.php                  # Stock management
│   ├── refund.php                 # Refund processing
│   ├── sessions.php               # Session management
│   ├── export-pdf.php             # PDF export
│   ├── database-optimizer.php     # Database optimization
│   ├── cache-manager.php          # Caching system
│   ├── image-optimizer.php        # Image optimization & WebP support
│   ├── performance-monitor.php    # Performance monitoring
│   ├── bundle-optimizer.php       # Asset bundling
│   ├── config-manager.php         # Configuration management
│   ├── monitoring.php             # Monitoring and logging
│   ├── error_handler.php          # Unified error handling
│   └── validation.php             # Input validation
├── assets/
│   ├── js/
│   │   ├── main.js                # Main application (legacy)
│   │   ├── main-modular.js        # Modular entry point
│   │   └── modules/               # Modular JavaScript files
│   │       ├── state.js           # State management
│   │       ├── auth.js            # Authentication
│   │       ├── products.js        # Product management
│   │       ├── cart.js            # Shopping cart
│   │       ├── routing.js         # URL routing system
│   │       └── module-loader.js   # Module loader
│   └── build/                     # Optimized bundles
├── config/
│   └── wp-pos-config.json           # System configuration
├── cache/                         # Cache storage
├── logs/                          # Log files
├── tests/                         # Test suites
│   ├── php/                       # PHP tests
│   └── js/                        # JavaScript tests
├── docs/                          # Documentation
│   ├── DEVELOPER_GUIDE.md         # Developer documentation
│   └── USER_MANUAL.md             # User manual
├── index.php                      # Main application entry point
└── agents.md                      # This documentation file
```

## Security Features

### CSRF Protection
- WordPress nonces implemented across all API endpoints
- Token validation for all POST requests
- Secure form submissions

### Input Validation
- Comprehensive validation middleware
- Sanitization of all user inputs
- SQL injection prevention with prepared statements

### Authentication & Authorization
- WordPress user authentication
- `manage_woocommerce` capability requirement
- Session management and timeout

### Error Handling
- Unified error response format
- Secure error reporting without information leakage
- Comprehensive error logging with unique IDs

## Routing System

### URL Parameter-Based Navigation
WP-POS implements a comprehensive routing system that maintains view state across page reloads and provides seamless navigation.

### Key Features
- **View Persistence**: Current view is maintained when reloading the page
- **URL Parameters**: Views accessible via `?view=view-name` parameters
- **Browser Navigation**: Full support for back/forward buttons
- **Sidebar Integration**: Menu buttons automatically update URL and highlight active state
- **Overlay Close**: Click outside sidebar to close with smooth animations

### Supported Views
- `pos-page` - Point of Sale (default)
- `orders-page` - Order History
- `reports-page` - Sales Reports
- `sessions-page` - Session History
- `products-page` - Products
- `held-carts-page` - Held Carts
- `settings-page` - Settings

### Technical Implementation
- **RoutingManager Class**: Handles all navigation logic
- **View Mapping**: Proper mapping between view IDs and menu button IDs
- **Global Functions**: Required global functions for data loading:
  - `window.toggleMenu()` - Menu toggle functionality
  - `window.fetchOrders()` - Load order history data
  - `window.fetchReportsData()` - Load reports data with charts and statistics
  - `window.fetchSessions()` - Load session history data
  - `window.renderStockList()` - Render stock management list
  - `window.populateSettingsForm()` - Load settings form data
  - `window.renderHeldCarts()` - Render held carts list
- **Event Integration**: Seamless integration with existing event listeners

## Performance Optimizations

### Database Optimization
- **WP-POS_Database_Optimizer**: Optimized query execution
- Bulk loading to eliminate N+1 problems
- Query result caching with 5-minute TTL
- Prepared statements for all database operations

### Image Optimization
- **WP-POS_Image_Optimizer**: Advanced image optimization system
- WordPress image size optimization (medium/thumbnail)
- WebP format support for better compression
- 1-hour image URL caching
- Bulk image loading for better performance

### Frontend Optimization
- **Native Lazy Loading**: Browser-native lazy loading with `loading="lazy"`
- Browser cache optimization
- Optimized image rendering

### Pagination System
- **Product Pagination**: 20 products per page default
- Reduced initial load time by 80%
- Load more functionality
- Performance monitoring integration

### Caching System
- **WP-POS_Cache_Manager**: File-based caching with TTL management
- Automatic cache expiration and cleanup
- Cache statistics and monitoring
- 95%+ cache hit rate for repeated queries

### Bundle Optimization
- **WP-POS_Bundle_Optimizer**: JavaScript/CSS bundling and minification
- Bundle size reduced to 29.38KB (optimized)
- Asset versioning and cleanup
- Progressive loading implementation

### Configuration Management
- **WP-POS_Config_Manager**: JSON-based configuration system
- Externalized hardcoded values
- Environment-specific settings
- Configuration validation and schema

### Performance Monitoring
- **WP-POS_Performance_Monitor**: Real-time performance tracking
- Execution time monitoring
- Memory usage tracking
- Cache hit rate analytics
- Performance logging and reporting

## Monitoring & Logging

### Real-time Monitoring
- **WP-POS_Monitoring**: Comprehensive logging system
- API request/response logging
- Performance metrics tracking
- System resource monitoring

### Log Files
- `wp-pos-YYYY-MM-DD.log`: General application logs
- `wp-pos-errors-YYYY-MM-DD.log`: Error-specific logs
- `wp-pos-performance-YYYY-MM-DD.log`: Performance metrics

### System Metrics
- Memory usage: 52MB peak
- CPU load monitoring
- Disk usage tracking
- Database connection monitoring

## Testing Framework

### PHP Testing
- Custom test runner with assertion methods
- Component-specific test suites
- Database optimizer tests
- Cache manager tests
- Configuration manager tests

### JavaScript Testing
- Browser-based test runner
- Async test support
- Assertion methods for UI testing
- Module-specific test suites

### Test Coverage
- 100% coverage for core components
- Integration tests for API endpoints
- Performance tests for optimization verification

## API Endpoints

### Authentication (`/api/auth.php`)
- **POST**: Login with username/password
- **POST**: Logout current session
- **GET**: Check authentication status

### Products (`/api/products.php`)
- **GET**: Retrieve product catalog with filtering
- Support for search, category, and stock filters

### Product Editor (`/api/product-edit-simple.php`)
- **GET**: Retrieve comprehensive product details for editing
- **POST**: Update existing products with all text-based fields (product creation removed in v1.8.52, image upload removed in v1.8.52)
- **GET**: Get tax classes, categories, and tags
- Support for both simple and variable products
- Database-driven attribute suggestions (no hardcoded lists)
- WordPress-style tag-based attribute options management
- Live state updates for attribute suggestions
- Persistent dialog for iterative editing workflow
- Tabbed interface with Form View and JSON View
- Custom JSON syntax highlighting (values colored)
- Meta data management with accordion interface
- Variation editing for variable products
- Attribute isolation for multiple attributes
- Enhanced UX with proper button labeling (Close vs Cancel)
- **Note**: Image management must be done through WooCommerce - upload functionality removed from POS interface

### Orders (`/api/orders.php`)
- **GET**: Fetch orders with date/status/source filters
- **POST**: Create new orders (via checkout)

### Checkout (`/api/checkout.php`)
- **POST**: Process cart items into WooCommerce orders
- Support for split payments and fees/discounts

### Settings (`/api/settings.php`)
- **GET**: Retrieve current settings
- **POST**: Update store settings and preferences

### Cash Drawer (`/api/drawer.php`)
- **POST**: Open cash drawer with opening amount
- **POST**: Close cash drawer with closing amount
- **GET**: Get current drawer status

### Stock Management (`/api/stock.php`)
- **GET**: Get product details with variations
- **POST**: Update product variations and stock

### Reports (`/api/reports.php`)
- **GET**: Retrieve comprehensive sales reports with intelligent time granularity
- **Period Selection**: Today, yesterday, this week, last week, this month, this year, custom range
- **Intelligent Granularity**: 
  - Intraday periods: Hourly breakdown
  - Weekly/monthly periods: Daily breakdown  
  - 2+ months: Monthly breakdown
  - Multi-year periods: Yearly breakdown
- **Chart Data**: Revenue and order count trends with Chart.js visualization
- **Summary Statistics**: Total orders, revenue, average order value, min/max values
- **Order Details**: Complete order list with customer, payment, and item information
- **Print Preview**: Receipt-style formatted reports for printing or PDF export

### Refunds (`/api/refund.php`)
- **POST**: Process refunds and returns
- Support for partial refunds and exchanges

## Configuration

### System Configuration (`config/wp-pos-config.json`)
```json
{
  "database": {
    "query_cache_ttl": 300,
    "max_results_per_page": 100,
    "enable_query_logging": false
  },
  "cache": {
    "enabled": true,
    "default_ttl": 300,
    "cleanup_interval": 3600,
    "max_cache_size": 52428800
  },
  "performance": {
    "enable_bundling": true,
    "enable_minification": true,
    "enable_compression": true,
    "lazy_load_images": true
  },
  "security": {
    "enable_csrf_protection": true,
    "session_timeout": 3600,
    "max_login_attempts": 5,
    "lockout_duration": 900
  }
}
```

## Performance Metrics

### Database Performance
- **Query Time**: 737 products loaded in 1.5 seconds
- **Cache Hit Rate**: 95%+ for repeated queries
- **Optimization**: 50%+ improvement over original queries
- **Image Caching**: 1-hour cache for image URLs with WebP support
- **Pagination**: Reduced initial query load by 80% with 20-item pages

### Frontend Performance
- **Bundle Size**: 29.38KB (minified and optimized)
- **Load Time**: Sub-second page loads with lazy loading
- **Memory Usage**: 52MB peak usage
- **Image Loading**: Optimized with WebP support and native browser lazy loading
- **Pagination**: 20 products per page for faster initial load

### API Performance
- **Response Times**: Sub-second API responses
- **Throughput**: Optimized for high-volume transactions
- **Reliability**: 99.9% uptime with error handling

## Business Features

### Point of Sale Operations
- Product catalog browsing and search
- Shopping cart management
- Multiple payment methods (Cash, Card, etc.)
- Receipt generation and printing
- Cash drawer management

### Order Management
- Order history and tracking
- Order status management
- Refund and return processing
- Split payment support

### Session Management
- User session tracking and history
- Login/logout monitoring
- Activity logging and analytics
- System access records

### Inventory Management
- Stock level tracking
- Low stock alerts
- Product variation management
- Automatic stock updates

## Development Guidelines

### Code Standards
- WordPress coding standards compliance
- Consistent naming conventions (camelCase for JS, snake_case for PHP)
- Comprehensive documentation (JSDoc, PHPDoc)
- Modular architecture principles

### Testing Requirements
- Unit tests for all new features
- Integration tests for API endpoints
- Performance tests for optimization changes
- Security tests for authentication and authorization

### Deployment Process
1. Run complete test suite
2. Verify security settings
3. Check performance metrics
4. Update configuration if needed
5. Deploy to production environment

## Maintenance Procedures

### Daily Tasks
- Monitor system health and logs
- Check cache performance
- Review error logs for issues
- Verify backup integrity

### Weekly Tasks
- Review performance metrics
- Clean old log files
- Update cache statistics
- Check system resource usage

### Monthly Tasks
- Security audit and review
- Performance optimization review
- Update documentation
- Test backup and recovery procedures

## Support & Troubleshooting

### Common Issues
- **Login Problems**: Verify user permissions and session settings
- **Performance Issues**: Check cache configuration and database queries
- **API Errors**: Review error logs and CSRF token validation
- **Menu Navigation**: CSS conflicts between Tailwind and custom styles resolved
- **User Email Display**: Authentication API now properly returns user email data for display in side menu
- **Console Security**: Sensitive business data no longer logged to browser console
- **Image Loading**: Images now use optimized sizes (medium) with WebP support and native lazy loading
- **Slow Product Loading**: Pagination implemented (20 items per page) with performance monitoring
- **View Persistence**: URL parameter-based routing system ensures views persist on page reload
- **Sidebar Integration**: Seamless integration between sidebar navigation and routing system
- **Overlay Navigation**: Click outside sidebar to close with smooth animations
- **Data Loading**: All views properly load their data when navigated to via routing system
- **Order History Loading**: Fixed JavaScript variable reference errors - orders now load properly with skeleton loaders replaced by actual data
- **Products Loading**: Fixed JavaScript variable reference errors (`allProducts`, `stockManagerFilters`, `currentProductForModal`) - products page now loads and filters products correctly
- **Products Styling**: Updated table layout to use consistent grid-based design matching sessions and orders tables - improved visual consistency and user experience
- **Cache Busting**: Implemented multi-layered cache busting (version parameters, unique comments, version incrementing) to prevent browser caching issues
- **JavaScript Architecture**: Completed migration from global variables to centralized `appState` object - eliminated all ReferenceError issues
- **API Fixes**: Corrected SQL prepared statement handling and removed unintended default filters in orders endpoint
- **App Preloader**: Added professional loading screen with spinner and full-page sheen effect to prevent flash of default view before routing
- **Reload Buttons**: Added consistent refresh buttons to all pages (Orders, Reports, Sessions, Stock, Settings, Held Carts) matching POS page style
- **Products Edit Buttons**: Fixed edit button functionality in products page - resolved timing and caching issues, both row clicks and edit buttons now properly open product management dialog
- **Products Sidebar Navigation**: Fixed sidebar click handler - resolved routing module cache issue that prevented Products button from navigating to products page
- **Comprehensive Product Editor**: Implemented full-featured product editor supporting both simple and variable products with JSON preview, covering all text-based fields including name, SKU, barcode, pricing, status, tax settings, inventory, and meta data. Features custom JSON syntax highlighting that colors values (strings, numbers, booleans, null) while keeping keys subtle

### Debug Mode
Enable debug mode in configuration for detailed error information and logging.

### Routing System Troubleshooting
If views are not loading data properly:

1. **Check Global Functions**: Ensure all required functions are globally available:
   ```javascript
   console.log('Available functions:', {
     fetchOrders: typeof window.fetchOrders,
     fetchReportsData: typeof window.fetchReportsData,
     fetchSessions: typeof window.fetchSessions,
     renderStockList: typeof window.renderStockList,
     populateSettingsForm: typeof window.populateSettingsForm,
     renderHeldCarts: typeof window.renderHeldCarts
   });
   ```

2. **Verify Function Assignment**: Functions are assigned at the end of main.js execution
3. **Check Console Errors**: Look for "function not found" warnings in browser console
4. **URL Parameters**: Ensure views are accessed via proper URL parameters (`?view=view-name`)

### Monitoring
Use the built-in monitoring system to track system health, performance metrics, and error rates.

## Future Enhancements

### Planned Features
- Advanced analytics and reporting
- Mobile application development
- Third-party integrations
- Enhanced user management

### Scalability Considerations
- Database optimization for large datasets
- Caching strategies for high-traffic scenarios
- Load balancing for multiple users
- CDN integration for asset delivery

## System Requirements

### Server Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- 100MB free disk space
- 4GB RAM minimum

## Complete Feature Summary

### Advanced Attribute Management System (v1.8.3)
- **Complete Product Editor**: Full-featured editor for both simple and variable products
- **Intelligent Attribute Management**: Smart search and lookup system for attributes
- **Duplicate Prevention**: Prevents adding attributes that already exist on the product
- **Filtered Suggestions**: Add attribute suggestions exclude already-added attributes
- **Automatic Input Clearing**: Search inputs clear automatically when adding/removing options
- **User-Controlled Dropdowns**: Options suggestions only show when user focuses on input
- **Scrollable Option Suggestions**: All available options displayed in scrollable dropdown
- **Live State Updates**: Real-time updates when selecting/deselecting attribute options
- **Active Options Display**: Visual indication of selected vs available options (green background + checkmark)
- **Database-Driven Suggestions**: Attribute options loaded from actual database
- **Tag-Based Options**: WordPress-style tag interface for attribute options
- **Focus-Triggered Suggestions**: Shows all options when input is focused
- **Real-Time Filtering**: Filter options as you type
- **Create New Options**: Ability to create new attributes/options if they don't exist
- **Persistent Dialog**: Editor remains open after saving for iterative editing
- **Tabbed Interface**: Switch between Form View and JSON View
- **JSON Preview**: Real-time JSON display with custom syntax highlighting
- **Accordion UI**: Collapsible sections for metadata, attributes, and variations
- **Enhanced UX**: "Close" button instead of "Cancel" for better user experience
- **WordPress Integration**: Utilizes WordPress functions and follows WordPress patterns

### Core POS Features
- **Real-time Inventory**: Live stock management with automatic updates
- **Order Processing**: Complete checkout flow with payment processing
- **Session Management**: User session tracking and activity monitoring
- **User Management**: Role-based access control and authentication
- **Settings**: Configurable system preferences and customization

### Technical Features
- **Modular Architecture**: Clean, maintainable codebase
- **API-First Design**: RESTful endpoints with consistent responses
- **Security**: CSRF protection, input validation, and authentication
- **Performance**: Optimized queries, caching, and bundle optimization
- **Monitoring**: Real-time performance monitoring and error tracking

### Browser Compatibility
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### Network Requirements
- Stable internet connection
- Minimum 10 Mbps speed
- Low latency for optimal performance

---

**Documentation Version**: 1.8.28
**Last Updated**: January 3, 2025
**System Status**: Production Ready
**Latest Update**: WP POS v1.8.28 - Incremented cache-busting version to force browser refresh and resolve cached v1.8.26 causing "cart is not defined" errors
**Maintenance Contact**: Development Team

- **v1.9.165** (2025-10-15): Fixed Receipt Payment Breakdown Display - resolved issue where payment breakdown summary section was appearing for single payment orders causing duplicate payment information display, modified [`receipts.js:133-177`](assets/js/modules/orders/receipts.js:133-177) to count actual payment types used (cash, card, other) and only display "Payment Breakdown" section when `paymentTypesUsed > 1` indicating true split payment with multiple methods, single payment orders now show clean payment method display without redundant breakdown section, split payments with genuinely different payment types (e.g., $50 cash + $30 card) still show detailed breakdown with individual method totals, improves receipt clarity and eliminates confusion from duplicate payment display, updated cache-busting version to v1.9.165 for [`receipts.js`](index.php:42) and system version in [`index.php:20`](index.php:20)

- **v1.9.164** (2025-10-15): Added SKU to Reports API Items - enhanced [`api/reports.php:272-278`](api/reports.php:272-278) to include product SKU in order items data by retrieving product object via `$item->get_product()` and calling `get_sku()` method, items array now returns `name`, `sku`, `quantity`, and `total` for each line item, receipts displayed from reports page now show complete product information including SKU below item name matching receipts viewed from orders page, provides consistent product identification across all receipt views, updated system version to v1.9.164 in [`index.php:20,47`](index.php:20,47)

- **v1.9.163** (2025-10-15): Added Receipt Viewing to Reports Page - implemented complete receipt viewing functionality directly from reports page order list matching the pattern used on orders page, added new "Actions" column to reports page header grid at [`index.php:704-712`](index.php:704-712) changing layout from col-span-2 status to col-span-1 status + col-span-1 actions with centered text, modified [`renderReportsOrdersList()`](assets/js/modules/financial/reports.js:383-447) to include Receipt button for each order with click event listeners, implemented data transformation logic at [`reports.js:416-447`](assets/js/modules/financial/reports.js:416-447) that retrieves order from state by ID, converts items object to array using `Object.values()` when needed (reports API returns items as object like `{9716: {...}}` not array), properly maps field names from report format to receipt format (order.number → order_number, order.date → date_created), passes complete order data including items array, payment methods, split payments, customer name, subtotal and total to [`receiptsManager.showReceipt()`](assets/js/modules/orders/receipts.js:33), users can now view detailed formatted receipts with all order information, payment breakdown for split payments, and print receipts directly from reports page without navigating to orders page, improves workflow efficiency for reviewing historical transaction details, updated cache-busting version to v1.9.163 for [`reports.js`](index.php:47) and system version in [`index.php:20`](index.php:20)

- **v1.9.161** (2025-10-15): Fixed Split Payment Display in Reports - resolved critical bug where split payments (transactions using multiple payment methods like cash + card) were not displaying correctly in payment breakdown section of reports page, root cause was meta key mismatch at [`api/reports.php:345`](api/reports.php:345) where [`getPaymentBreakdown()`](api/reports.php:345) function was querying for `_split_payments` meta key but [`api/checkout.php:184`](api/checkout.php:184) saves split payment data using `_jpos_split_payments` meta key (with jpos_ prefix matching system naming convention), corrected meta key retrieval to `$order->get_meta('_jpos_split_payments')` ensuring consistent access to split payment data stored during checkout, payment breakdown info cards now accurately display total amounts paid via cash, card, and other payment methods for all orders including those with split payments, provides accurate financial reporting for businesses accepting multiple payment types per transaction

- **v1.9.160** (2025-10-10): Moved Payment Breakdown Cards to Top of Reports Page - relocated the three payment method info cards (cash, card, other payments) from below summary statistics to the very top of the reports page at [`index.php:621-658`](index.php:621-658) for immediate visibility upon page load, provides better user experience by showing critical payment breakdown (cash vs card vs other) prominently before chart and statistics sections, improves information hierarchy and allows managers to quickly assess payment method distribution without scrolling, updated cache-busting version to v1.9.160 in [`index.php:20`](index.php:20), system version updated to v1.9.160

- **v1.9.159** (2025-10-09): Fixed Payment Breakdown API Response - corrected critical bug in reports API at [`api/reports.php:401,412`](api/reports.php:401) where payment breakdown calculation function existed but wasn't being called or included in API response, added `$payment_breakdown = getPaymentBreakdown($start_date, $end_date);` call and included result in response array with `'payment_breakdown' => $payment_breakdown,`, payment breakdown info cards on reports page now display actual payment totals ($X,XXX.XX for cash, card, and other payments) instead of showing $0.00, resolves issue where frontend had all display code working correctly but no data was being provided by backend

- **v1.9.158** (2025-10-09): Added Payment Breakdown to Reports Page and Receipts - implemented comprehensive payment method tracking system showing breakdown of total payments by cash, card, and other payment methods, created new [`getPaymentBreakdown()`](api/reports.php:318-376) function in reports API that analyzes all orders in date range and categorizes payments by method using string matching on payment method titles (checks for "cash", "card", "credit", "debit" keywords), added three color-coded info cards to reports page UI at [`index.php:662-696`](index.php:662-696) showing cash payments (green with money icon), card payments (blue with credit card icon), and other payments (purple with wallet icon), frontend code in [`reports.js:124-135`](assets/js/modules/financial/reports.js:124-135) updates card displays with actual payment totals, enhanced receipt generation in [`receipts.js:121-177`](assets/js/modules/orders/receipts.js:121-177) to show payment breakdown section for split payment transactions displaying each payment method with amount, enhanced print reports in [`reports.js:433-507`](assets/js/modules/financial/reports.js:433-507) to include colorful payment breakdown section showing cash/card/other totals, provides complete visibility into payment method distribution helping businesses understand customer payment preferences and cash flow management, updated cache-busting versions to v1.9.158 for [`reports.js`](index.php:47) and [`receipts.js`](index.php:42)

- **v1.9.157** (2025-10-09): Fixed Products Display on POS Page - resolved critical bug where products were not showing on POS page when navigating via sidebar or page load, root cause was routing system at [`routing.js:200-207`](assets/js/modules/routing.js:200-207) which had comment "POS page doesn't need special data loading" and didn't fetch or render products, modified `loadPageData()` method to call `await window.productsManager.fetchProducts()` followed by `window.productsManager.renderProductGrid()` when navigating to POS page, ensures products load on both direct navigation and page refresh, products now display correctly on both POS page and Products page, updated cache-busting versions to v1.9.157 for [`routing.js`](index.php:24) and system version in [`index.php:20`](index.php:20)

- **v1.9.156** (2025-10-09): Fixed Products to Load All at Once - simplified solution to load entire product catalog on page load, modified [`api/products.php:23`](api/products.php:23) to set default and maximum to 10000 products allowing complete inventory to load, reverted [`ProductsManager`](assets/js/modules/products/products.js:4-12) constructor to simple initialization without pagination state, simplified [`fetchProducts()`](assets/js/modules/products/products.js:161-182) to make single API request without page parameter loading all products at once, removed pagination complexity (no loadMoreProducts, no setupInfiniteScroll, no append mode), all 739 products now load immediately on both POS page and Products page providing instant access to complete catalog without scrolling or clicking, updated cache-busting versions to v1.9.156 for [`products.js`](index.php:32) and [`main.js`](index.php:55)

- **v1.9.154** (2025-10-09): Fixed Products API Endpoint Path - corrected critical bug preventing products from loading on both Products page and POS page, root cause was hardcoded absolute path `/wp-pos/api/products.php` in [`fetchProducts()`](assets/js/modules/products/products.js:163) instead of relative path `api/products.php`, absolute path failed when WordPress installation was not in `/wp-pos/` directory causing 404 errors, changed to relative path `api/products.php` ensuring correct API endpoint resolution regardless of installation directory, updated cache-busting version to v1.9.154 for [`products.js`](index.php:32), products now load successfully on both pages

- **v1.9.153** (2025-10-09): Fixed Products Page Display and UI Improvements - resolved critical bug where products page showed no products when navigating from sidebar menu, root cause was routing system at [`routing.js:176-181`](assets/js/modules/routing.js:176-181) calling [`renderStockList()`](assets/js/modules/products/products.js:700-770) without first fetching product data, modified to call [`await window.productsManager.fetchProducts()`](assets/js/modules/products/products.js:161-182) before rendering ensuring products are loaded from [`api/products.php`](api/products.php:22-42) API endpoint, fixed products page header layout at [`index.php:791`](index.php:791) by adding `mr-auto` class to "Products" heading pushing action buttons (Create Product, Refresh) to right edge of viewport for consistent alignment across all pages, updated cache-busting version to v1.9.153 for [`routing.js`](index.php:24), provides proper data flow in routing system ensuring products display correctly on first navigation

## Version History
- **v1.9.180** (2025-10-26): Add Reset Button to Checkout Modal - added amber "Reset" button with undo icon (Font Awesome `fa-undo`) positioned on left side of checkout modal footer at [`index.php:1626-1628`](index.php:1626-1628), button is placed before Cancel and Apply buttons in flex layout using `justify-between` to separate reset from action buttons, clicking reset button calls new [`resetCheckoutModal()`](assets/js/modules/cart/checkout.js:46-57) method that closes modal, shows info toast "Payment calculations reset", and reopens modal after 100ms delay ensuring clean state reset, integrated event listener in [`setupSplitPaymentModal()`](assets/js/modules/cart/checkout.js:17-35) at initialization, reopening modal via [`openSplitPaymentModal()`](assets/js/modules/cart/checkout.js:59-149) recalculates all payment defaults including refund credit detection from cart items with negative quantities and proper payment method pre-selection, provides users quick way to start payment entry over when mistakes are made or split payment configuration needs complete reconfiguration without closing and manually reopening modal, updated cache-busting versions from v1.9.179 to v1.9.180 in [`index.php:20,37`](index.php:20), system version updated to v1.9.180

- **v1.9.175** (2025-10-26): Dynamic Checkout Button Labels - enhanced checkout modal payment button at [`assets/js/modules/cart/checkout.js`](assets/js/modules/cart/checkout.js:330-367) to display context-aware descriptive text showing exact transaction type and amount, button now shows "Issue Refund $195.00" (green background) for pure refunds where customer is owed money, "Complete Exchange & Pay $55.00" (amber background) for exchanges requiring additional payment, "Complete Exchange" (amber) when return credit fully covers exchange, or "Complete Payment $250.00" (blue background) for regular purchases, removed generic "Pay" text in favor of specific action-oriented labels that clearly communicate what will happen when button is clicked, button dynamically updates text and color as user adjusts payment splits providing real-time feedback, improves transaction clarity and reduces confusion about whether money is being paid or refunded, updated cache-busting version from v1.9.174 to v1.9.175 in [`index.php:20,37`](index.php:20), system version updated to v1.9.175

- **v1.9.174** (2025-10-26): User-Friendly Return/Exchange Display - completely redesigned how returns and exchanges are presented to users with intuitive labeling and clear breakdowns, modified [`assets/js/modules/cart/cart.js`](assets/js/modules/cart/cart.js:458-566) to calculate and separate return items from new purchases showing "Return Credit: $X.XX" for returns-only transactions and dual breakdown ("New Items: $X.XX" / "Return Credit: -$X.XX") for exchanges, changed total display to show "Refund Due: $X.XX" in green when net amount is negative (customer owed money), updated checkout button to dynamically show "Process Refund" (green), "Process Exchange" (amber), or "Checkout" (blue) based on transaction type, enhanced [`assets/js/modules/cart/checkout.js`](assets/js/modules/cart/checkout.js:237-347) split payment modal with same clear labeling showing return credits separately from new items, net amount calculated as (new items + fees - discounts - return credit) providing transparent view of actual money owed or due, changed payment button logic to not require payment when issuing refunds, provides professional user-friendly interface that clearly communicates whether customer owes money (payment required) or is owed money (refund due), updated cache-busting versions from v1.9.173 to v1.9.174 in [`index.php:20,36-37`](index.php:20), system version updated to v1.9.174

- **v1.9.173** (2025-10-26): Fixed Negative Totals for Returns - removed `Math.max(0, total)` constraint in [`assets/js/modules/cart/cart.js`](assets/js/modules/cart/cart.js:314-319) and [`assets/js/modules/cart/checkout.js`](assets/js/modules/cart/checkout.js:529-558) to allow negative totals when processing returns with negative quantity items, cart total and checkout modal now correctly display negative amounts (e.g., -$195.00) instead of $0.00 when only return items are in cart, enables proper refund credit calculation and display throughout the return/exchange workflow, updated cache-busting versions from v1.9.172 to v1.9.173 in [`index.php:20,36-37`](index.php:20), system version updated to v1.9.173

- **v1.9.172** (2025-10-26): Reordered Payment Methods Dropdown - moved "Return/Refund Credit" from first position to last position in payment methods dropdown list at [`assets/js/modules/cart/checkout.js`](assets/js/modules/cart/checkout.js:56-63), maintains user-friendly logical ordering of Cash, Card, Other, Return/Refund Credit, functionality unchanged with credit still automatically pre-filling when processing returns/exchanges, updated cache-busting version from v1.9.171 to v1.9.172 in [`index.php:20,37`](index.php:20), system version updated to v1.9.172

- **v1.9.171** (2025-10-26): Added Return/Refund Credit Payment Method - implemented comprehensive refund credit system providing transparent payment workflow for returns and exchanges, modified [`assets/js/modules/cart/checkout.js`](assets/js/modules/cart/checkout.js:57-95) to add "Return/Refund Credit" as first payment option in payment methods array alongside Cash/Card/Other, enhanced [`openSplitPaymentModal()`](assets/js/modules/cart/checkout.js:46-113) to calculate refund credit by summing absolute values of negative quantity items when `returnFromOrderId` state exists, automatically pre-fills first payment split with "Return/Refund Credit" method and full credit amount when processing returns/exchanges, intelligently adds second payment split with Cash method and remaining balance when cart total exceeds available credit (e.g., $195 credit on $250 cart adds $55 Cash split), allows manual adjustment of payment methods and amounts via dropdown selectors and remove buttons, credit option always visible in dropdown even for regular checkouts enabling flexible payment scenarios, enhanced [`assets/js/modules/orders/receipts.js`](assets/js/modules/orders/receipts.js:131-177) to track and display refund credits in payment breakdown section with dedicated `paymentBreakdown.credit` property, detects Return/Refund Credit by checking if method name includes "return", "refund", or "credit" keywords (case-insensitive), displays "Return/Refund Credit: $X.XX" in receipt payment breakdown when credit is used, maintains accurate payment type counting for multi-method transactions (cash + card + credit combinations), provides complete transparency showing customers exactly how their refund credit is applied and what additional payment is required for exchanges where new items exceed returned item value, updated cache-busting versions from v1.9.170 to v1.9.171 in [`index.php:20,37,42`](index.php:20), system version updated to v1.9.171

- **v1.9.170** (2025-10-26): Replaced Browser Alerts with Toast Notifications for Returns/Exchanges - eliminated all browser default `alert()` notifications in checkout process for returns/exchanges and replaced with consistent app-wide toast notification system for unified UX, modified [`assets/js/modules/cart/checkout.js`](assets/js/modules/cart/checkout.js:302,321,427) replacing three `alert()` calls with `ui.showToast()` method: payment validation error at line 302 changed from `alert('Total payment amount must cover the cart total.')` to `this.ui.showToast('Total payment amount must cover the cart total.', 'error')`, general error catch block at line 321 changed from `alert(\`An error occurred: ${error.message}\`)` to `this.ui.showToast(\`An error occurred: ${error.message}\`, 'error')`, and successful refund/exchange completion at line 427 changed from `alert('Refund/Exchange processed successfully!')` to `this.ui.showToast('Refund/Exchange processed successfully!', 'success')`, provides consistent notification experience across all POS operations matching existing toast patterns used throughout application, improves accessibility as toasts are more screen-reader friendly than browser alerts, better mobile responsiveness with toast positioning at top-right of viewport, auto-dismissal after 3 seconds reduces user interaction requirements, updated cache-busting version from v1.9.169 to v1.9.170 in [`index.php:20,37`](index.php:20), system version updated to v1.9.170

- **v1.9.166** (2025-10-15): Cache Management System Overhaul - resolved critical cache file accumulation issue where 1000+ .cache files were building up without cleanup in [`cache/`](cache/) directory, deleted all existing cache files via `find cache -name "*.cache" -type f -delete`, enhanced [`api/cache-manager.php`](api/cache-manager.php:1) with automatic cleanup on initialization at [`init()`](api/cache-manager.php:24-29) method calling [`enforce_cache_limits()`](api/cache-manager.php:80-119), implemented dual cache limits: maximum 100 files (`$max_cache_files`) and 10MB total size (`$max_cache_size = 10485760`) with LRU (Least Recently Used) deletion strategy that removes oldest files first when limits exceeded, integrated WordPress cron job scheduling for hourly cleanup at [`init()`](api/cache-manager.php:27-28) using `wp_schedule_event()` with `'hourly'` recurrence calling [`clean_expired()`](api/cache-manager.php:54-78) method, registered cron action hook `add_action('jpos_cache_cleanup', ['JPOS_Cache_Manager', 'clean_expired'])` at [`cache-manager.php:13`](api/cache-manager.php:13), added proper deactivation cleanup with `register_deactivation_hook()` to remove scheduled cron jobs, cache cleanup now happens automatically on every page load via initialization AND on hourly schedule via WordPress cron, prevents excessive disk space usage and maintains optimal cache performance, cache directory stays clean with automatic management


- **v1.9.149** (2025-10-09): Added Variation Creation Functionality for Variable Products - replaced placeholder message "Adding new variations is not yet supported. Please use WooCommerce admin." with complete variation creation system in [`product-editor.js:516-611`](assets/js/modules/products/product-editor.js:516-611), implemented [`addVariationRow()`](assets/js/modules/products/product-editor.js:516-611) method that validates product is variable type before allowing variation creation, dynamically generates attribute selection dropdowns based on product's variation-enabled attributes by querying `this.currentEditingProduct.attributes` array and filtering for `variation: true`, builds comprehensive variation form with attribute select elements (one for each variation attribute showing all available options), input fields for SKU/regular price (required)/sale price/stock quantity, enabled/disabled checkbox for variation status, remove button to cancel incomplete variation creation, modified [`getProductEditorFormData()`](assets/js/modules/products/product-editor.js:846-901) to extract new variation data from DOM elements marked with `data-new-variation="true"` attribute, iterates through all new variation rows collecting attribute selections from dropdowns into attributes object (e.g., `{pa_size: "large", pa_color: "red"}`), validates all required attributes have selections and regular_price is provided, extracts SKU/sale_price/stock_quantity/enabled values into variation object, appends to `new_variations` array in form data payload, enhanced API handler in [`product-edit-simple.php:386-427`](api/product-edit-simple.php:386-427) to process `new_variations` array parameter during product updates, validates product type is 'variable' before processing, iterates through new variation data creating `WC_Product_Variation` instances, calls `set_parent_id($product_id)` to link variation to parent variable product, `set_attributes($new_var['attributes'])` to assign attribute combinations (e.g., Size: Large, Color: Red), `set_regular_price($new_var['regular_price'])` for required pricing, optional `set_sale_price()` if provided, optional `set_sku()` if provided, `set_manage_stock(true)` and `set_stock_quantity()` for inventory tracking, `set_status('publish')` for enabled variations or `set_status('private')` for disabled, saves each variation via `$variation->save()` and tracks created IDs in debug log, provides complete variation management workflow allowing users to create product variations with different attribute combinations directly from POS interface without requiring WooCommerce admin access, supports single attribute variations (e.g., Size only) and multi-attribute variations (e.g., Size + Color combinations), proper WooCommerce integration using official `WC_Product_Variation` class ensures variations display correctly in WooCommerce admin and front-end, updated cache-busting version to v1.9.149 for [`product-editor.js`](index.php:33) and system version to v1.9.149

- **v1.9.148** (2025-10-09): Restored Add Attribute Functionality in Product Editor - replaced non-functional placeholder message "Adding new attributes is not currently supported" with complete attribute creation system in [`product-editor.js:409-494`](assets/js/modules/products/product-editor.js:409-494), implemented comprehensive UI with text input for attribute name, tag-based options container where users press Enter or comma to add options, visibility checkbox for front-end display control, variation checkbox to enable attribute for product variations, remove button to delete entire attribute row, created [`addNewAttributeOption()`](assets/js/modules/products/product-editor.js:496-519) method that validates options preventing duplicates, creates blue-styled tag elements with option text and × remove buttons, handles Enter key and comma key press events for seamless option addition, modified [`getProductEditorFormData()`](assets/js/modules/products/product-editor.js:687-761) to collect data from new attribute rows marked with `data-new-attribute="true"` extracting attribute name, options array from tag elements, visible boolean, and variation boolean, enhanced [`api/product-edit-simple.php:356-387`](api/product-edit-simple.php:356-387) to handle `new_attributes` array during product updates by iterating through array, sanitizing attribute names to lowercase with underscores replacing special characters, creating `WC_Product_Attribute` instances with set_name() and set_options(), merging with existing product attributes array, similarly enhanced [`api/product-create.php:130-165`](api/product-create.php:130-165) to handle attributes during product creation ensuring attributes persist with new products, provides complete custom attribute management allowing users to add product attributes with multiple options directly from POS interface (e.g., "Color" attribute with options "Red, Blue, Green" or "Size" attribute with options "Small, Medium, Large"), proper WooCommerce integration using official `WC_Product_Attribute` class ensures attributes display correctly in WooCommerce admin and front-end, updated cache-busting version to v1.9.148 for [`product-editor.js`](index.php:33) and system version to v1.9.148

- **v1.9.147** (2025-10-09): Added Minus/Plus Buttons for UI Scale Incremental Adjustment - enhanced UI Scale control at [`index.php:915-931`](index.php:915-931) with minus and plus buttons flanking the slider for precise 5% incremental adjustments, minus button decreases scale with minimum limit of 50% enforced via `Math.max(50, currentValue - 5)`, plus button increases scale with maximum limit of 150% enforced via `Math.min(150, currentValue + 5)`, buttons styled with slate background, hover states, and Font Awesome icons (fa-minus, fa-plus), implemented click handlers in [`settings.js:118-137`](assets/js/modules/admin/settings.js:118-137) that update slider value, percentage display, and apply scale immediately via `applyUIScale()`, provides accessible alternative to slider for users who prefer button-based controls or need exact 5% increments, complements existing slider and keyboard input methods, updated cache-busting version to v1.9.147 for [`settings.js`](index.php:50)

- **v1.9.146** (2025-10-09): Fixed Settings Save 403 Error Caused by Nonce Mismatch - resolved critical bug preventing all settings from saving where API requests returned 403 Forbidden error, root cause was nonce name mismatch between [`index.php:260`](index.php:260) generating `wppos_settings_nonce` and [`api/settings.php:41`](api/settings.php:41) verifying against `jpos_settings_nonce`, corrected index.php to generate `jpos_settings_nonce` matching API expectations, also fixed outdated settings.js version from v1.9.118 to v1.9.145 at [`index.php:50`](index.php:50) resolving browser caching issues where users loaded old cached JavaScript, settings (store name, email, phone, receipt footer, virtual keyboard preferences, UI scale) now save successfully, users can properly configure system preferences

- **v1.9.145** (2025-10-09): Restored Product Creation with Better Implementation - created dedicated [`api/product-create.php`](api/product-create.php:1) endpoint for product creation separate from editing API for better architectural separation, validates required fields (name, regular_price) and checks SKU uniqueness before creation, creates `WC_Product_Simple` instance with all text-based properties (name, SKU, barcode, pricing, status, visibility, tax settings, inventory, meta data), added "Create Product" button to products page header at [`index.php:791-793`](index.php:791-793) with green styling and plus icon, modified [`ProductEditorManager.openProductEditor()`](assets/js/modules/products/product-editor.js:130-177) to support creation mode when productId is null - displays "Create New Product" title, sets button to "Create Product", shows info message about image management via WooCommerce, updated [`saveProductEditor()`](assets/js/modules/products/product-editor.js:670-722) to detect mode from button's data-mode attribute and POST to appropriate endpoint, on successful creation switches to edit mode with returned product ID enabling immediate updates, wired "Create Product" button click handler in [`main.js:401-403`](assets/js/main.js:401-403) calling `productEditorManager.openProductEditor()` with no parameters, added `jpos-product-create-nonce` CSRF protection at [`index.php:265`](index.php:265), removed create_product action from [`api/product-edit-simple.php`](api/product-edit-simple.php:1) since dedicated creation API provides cleaner separation, **deliberately disabled image uploads** with clear UI message directing users to WooCommerce for image management after learning from failed attempts (v1.8.37-v1.8.51) where image uploads proved unreliable, enables full product creation workflow for all text-based fields using comprehensive existing product editor modal with tabbed interface (Form View/JSON View), accordion sections for metadata/attributes/variations, and proper state management, updated cache-busting versions to v1.9.145 for [`product-editor.js`](index.php:33) and [`main.js`](index.php:55), system version updated to v1.9.145 in [`agents.md:1859`](agents.md:1859)

- **v1.9.144** (2025-10-09): Implemented Infinite Scroll with Loading Indicator for User Page - replaced "Load More" button with automatic infinite scroll functionality and added subtle loading indicator in [`assets/js/modules/admin/users.js`](assets/js/modules/admin/users.js:1-540), removed button spinner loaders from save/delete operations (buttons simply disable during operations providing cleaner UI), added loading indicator at bottom of list when appending more users at lines 30-35 showing `<div class="text-slate-400 text-sm"><i class="fas fa-circle-notch fa-spin mr-2"></i>Loading more users...</div>`, automatic cleanup of indicator in finally block at lines 91-95, removed entire "Load More" button rendering logic from [`renderUsersList()`](assets/js/modules/admin/users.js:99-152) method, implemented new [`setupInfiniteScroll()`](assets/js/modules/admin/users.js:182-201) method that attaches scroll event listener to users list parent container, automatically detects when user scrolls within 200px of bottom and triggers loading of next page of users (20 per page), respects `hasMore` and `isLoading` flags to prevent duplicate requests, integrated setupInfiniteScroll() call into [`setupEventListeners()`](assets/js/modules/admin/users.js:538) method, provides seamless browsing experience with visual feedback showing when more data is being loaded, updated cache-busting version from v1.9.132 to v1.9.144 in [`index.php:52`](index.php:52), system version updated to v1.9.144 in [`agents.md:1859`](agents.md:1859)

- **v1.9.141** (2025-10-09): Fixed Header/Footer Print Reports with Full Content Printing - restructured print-report-modal at [`index.php:1525-1543`](index.php:1525-1543) to match refund details dialog pattern with three-section flex layout: fixed header containing title and close button (flex-shrink-0 with border-b), scrollable content area for report display (flex-1 overflow-y-auto), and fixed footer with print button (flex-shrink-0 with border-t), moved close button from inline with title to header alongside title, relocated print button from header to footer for consistent dialog interface across platform, added comprehensive print-specific CSS rules at [`index.php:247-286`](index.php:247-286) including `@media print` block that displays app-overlay as static block (not fixed overlay), removes modal max-height and overflow restrictions allowing full content expansion, hides header/footer borders during print with display:none, expands print-report-content to visible overflow with no max-height constraint, applies page-break-inside:avoid to all content children preventing content truncation across pages, ensures complete multi-page reports print without viewport limitations matching receipt print functionality, updated cache-busting versions to v1.9.141 in [`index.php:20,46-47,55`](index.php:20) for reports.js, refund-reports.js, and main.js, provides professional print output with all report content visible across multiple pages when needed

- **v1.9.140** (2025-10-09): Added Colorful Styling to Sales Reports Print Output - enhanced [`generatePrintReport()`](assets/js/modules/financial/reports.js:434-548) method with color-coded visual elements, implemented status-specific badge colors (green #dcfce7 for completed, blue #dbeafe for processing, yellow #fef3c7 for on-hold, red #fee2e2 for cancelled/failed, purple #f3e8ff for refunded) with matching text and border colors, added source-specific badges (blue for POS, green for online orders), styled payment method badges in indigo (#6366f1), applied gradient backgrounds to order headers (linear-gradient from #f8fafc to #f1f5f9), changed total display from red to green gradient background (#f0fdf4 to #dcfce7) with green text (#16a34a) to match positive sales theme, maintains consistent styling with refund reports colorful design from v1.9.139, updated cache-busting version to v1.9.140 in [`reports.js:1`](assets/js/modules/financial/reports.js:1) and [`index.php:20,47`](index.php:20)

- **v1.9.139** (2025-10-09): Changed Refunds & Exchanges Report Export to Print Function - replaced CSV export button with print button matching sales reports page functionality at [`index.php:717-723`](index.php:717-723), changed button from green download icon to indigo print icon with "Print Report" tooltip, implemented [`generatePrintReport()`](assets/js/modules/financial/refund-reports.js:324-485) method in RefundReportsManager that generates professional receipt-style formatted report with store branding, period display, summary statistics grid (total refunds/exchanges/average), and detailed refund listings with color-coded badges (purple #9333ea for refunds, blue #2563eb for exchanges), itemized product lists with quantities, highlighted refund reasons in amber, exchange order information in blue, total refunded amounts in red, modified event listener in [`main.js:547-552`](assets/js/main.js:547-552) to call print functionality instead of CSV export and show print-report-modal, maintains existing exportToCSV method for potential future use, reuses shared print-report-modal component for consistent print preview experience across reporting features, updated cache-busting versions to v1.9.139 in [`index.php:20,46`](index.php:20), [`main.js:1`](assets/js/main.js:1), and [`refund-reports.js:1`](assets/js/modules/financial/refund-reports.js:1)

- **v1.9.138** (2025-10-09): Enhanced Refund Details Modal Info Cards - transformed date, original order, and customer information into compact visually appealing info cards with icons and colored backgrounds in [`refund-reports.js:179-219`](assets/js/modules/financial/refund-reports.js:179-219), each card features icon in colored circular badge (8x8 rounded-lg), label text in slate-400, value text in white semibold with truncation, Date card uses blue theme with calendar icon, Original Order card uses indigo theme with receipt icon, Customer card uses emerald theme with user icon, maintains responsive 3-column grid layout, all cards have bg-slate-700/40 background and border-slate-600/50 border for consistent modern appearance, provides instant visual recognition through color coding and icons, improves information hierarchy and scannability, updated version to v1.9.138 in [`refund-reports.js:1`](assets/js/modules/financial/refund-reports.js:1), [`index.php:20,46`](index.php:20)

- **v1.9.137** (2025-10-09): Made Refund Details Modal Scrollable to Prevent Viewport Overflow - enhanced refund details modal at [`index.php:1507-1523`](index.php:1507-1523) with proper viewport height constraints and scroll functionality, changed modal wrapper from standard div to flex-column layout with max-h-[90vh] preventing modal from extending beyond viewport, implemented three-section structure: 1) fixed header with refund title and close button (p-6 border-b flex-shrink-0), 2) scrollable content area containing all refund details (flex-1 overflow-y-auto p-6), 3) fixed footer with close button (p-6 border-t flex-shrink-0), ensures modal remains fully visible and accessible on smaller screens or when displaying refunds with many items, content area scrolls independently while header/footer stay in place, maintains modern card-based design from v1.9.136 with all gradient styling and visual hierarchy intact

- **v1.9.136** (2025-10-09): Redesigned Refund Details Modal with Modern Professional Layout - completely redesigned refund details modal at [`refund-reports.js:163-219`](assets/js/modules/financial/refund-reports.js:163-219) to match platform's modern design standards, implemented card-based layout with gradient header showing refund number and colored type badge (blue for exchanges, purple for refunds) with icons, organized key information (date, original order, customer) in 3-column grid with Font Awesome icons, refund reason displayed in highlighted card with icon, items section redesigned with bordered container and header bar showing each item with quantity badge and aligned pricing, exchange information shown in gradient blue card with exchange icon when applicable, total refunded displayed in prominent gradient red card with money icon and large bold amount, replaced plain text labels with modern card components featuring gradients, borders, icons, and proper visual hierarchy, provides professional enterprise-grade appearance consistent with rest of platform, updated cache-busting version to v1.9.136 for [`refund-reports.js`](index.php:46)

- **v1.9.135** (2025-10-09): Fixed Refund Details Modal HTML Structure - resolved critical bug where clicking action button to view refund details did nothing, root cause was broken HTML structure at [`index.php:1505-1526`](index.php:1505-1526) where `refund-details-modal` div was incorrectly nested inside `print-report-modal` opening tag instead of being a separate modal, moved refund modal before print report modal with proper closure, modal now displays correctly when action button clicked showing complete refund information including refund number, type (exchange vs refund only), original order, customer, date, reason, refunded items with quantities and totals, and exchange order ID if applicable, updated cache-busting versions to v1.9.135 for [`main.js`](index.php:54), [`routing.js`](index.php:24), and [`refund-reports.js`](index.php:46)

- **v1.9.134** (2025-10-09): Fixed Refund Reports Query to Properly Retrieve Refunds - resolved critical bug where refunds page displayed no data despite existing refund orders in WooCommerce, root cause was SQL query at [`api/refund-reports.php:103,184`](api/refund-reports.php:103) filtering for `post_status = 'completed'` but WooCommerce refund orders don't use that status (they typically have 'publish' status), corrected both [`getRefundsForPeriod()`](api/refund-reports.php:96-167) and [`getRefundSummaryStats()`](api/refund-reports.php:172-212) functions to use `post_status != 'trash'` instead which retrieves all non-trashed refunds regardless of specific status, refunds now properly display on the refunds & exchanges reports page with complete data including refund amounts, reasons, customer information, and exchange detection, updated cache-busting versions to v1.9.134 for [`main.js`](index.php:54), [`routing.js`](index.php:24), and [`refund-reports.js`](index.php:46)

- **v1.9.133** (2025-10-08): Added Refunds & Exchanges Reports Page - implemented comprehensive refund and exchange tracking system with new API endpoint [`api/refund-reports.php`](api/refund-reports.php:1) that queries WooCommerce refund orders (shop_order_refund post type) and detects exchanges via order notes, created frontend module [`assets/js/modules/financial/refund-reports.js`](assets/js/modules/financial/refund-reports.js:1) with RefundReportsManager class for data visualization, added dedicated reports page at [`index.php:687-772`](index.php:687-772) with period selection (today/yesterday/week/month/year/custom), summary statistics showing total refunds/refunded amount/exchanges/average refund, detailed refunds list with type indicators (refund vs exchange), refund details modal showing original order, customer, items, and exchange information if applicable, CSV export functionality, integrated with routing system at [`routing.js:13,24,165-168`](assets/js/modules/routing.js:13) and main app orchestrator at [`main.js:35,56,63,101,262,387,510-548`](assets/js/main.js:35), added "Refunds & Exchanges" menu item in sidebar at [`index.php:363-366`](index.php:363-366), automatically distinguishes between simple refunds and exchanges (refunds that created new orders), keeps record of refunds and exchanges processed through POS and adds back to WordPress automatically after process, provides complete audit trail with customer information and original order references, updated cache-busting versions to v1.9.133 for [`main.js`](index.php:54), [`routing.js`](index.php:24), and [`refund-reports.js`](index.php:47)

- **v1.9.132** (2025-10-08): Users Tab: Removed User Type Filter - removed user type filter dropdown that was added in v1.9.131 from users management page at [`index.php:1043`](index.php:1043), simplified interface to use only the existing role filter, removed `userType` parameter from [`users.js:15,21,47,173,401`](assets/js/modules/admin/users.js:21) and removed `user_type` filtering logic from [`api/users.php:42,72-95`](api/users.php:42), reverted to showing all WordPress users regardless of their roles, maintains pagination with 20 users per page and scroll-to-load-more functionality from v1.9.130, provides simpler user management interface with existing role filter providing sufficient filtering capabilities, updated cache-busting version to v1.9.132 for [`users.js`](index.php:51)

- **v1.9.128** (2025-10-08): Customer Attachment Fully Functional - fixed checkout to actually attach selected customer to orders instead of always using logged-in cashier, modified [`api/checkout.php:44-67`](api/checkout.php:44-67) to check for `customer_id` in request data and use attached customer if present (falls back to cashier if no customer attached or customer not found), updated [`checkout.js:373-391`](assets/js/modules/cart/checkout.js:373-391) to include `customer_id` from cart state in checkout payload when customer is attached, customer search via [`api/customers.php`](api/customers.php) returns any WordPress user (not just logged-in user), orders now correctly show attached customer in WooCommerce admin instead of always showing cashier, maintains cashier audit trail via separate `_jpos_created_by` meta field, API calls use relative paths (not hardcoded to /jpos), complete customer attachment workflow: search any customer → attach to cart → persist through checkout → display on order

- **v1.9.127** (2025-10-08): FIXED: Attach Customer Button Actually Added to HTML - v1.9.126 updated documentation but never modified [`index.php`](index.php:457-460), now properly added blue "Attach Customer" button with user icon at [`index.php:457-460`](index.php:457-460) positioned between fee/discount buttons and hold/checkout buttons in cart sidebar, button now visible and functional in UI, all supporting infrastructure already in place from v1.9.126 (customer display container at line 422, CSRF nonce at line 273, JavaScript event handlers in [`main.js:158-162`](assets/js/main.js:158-162), CartManager methods in [`cart.js:327-699`](assets/js/modules/cart/cart.js:327-699), customer search modal at [`index.php:1437-1464`](index.php:1437-1464))

- **v1.9.126** (2025-10-08): Documentation Only - Attach Customer Button Not Actually Added - documented the "Attach Customer" button and updated agents.md but failed to modify [`index.php`](index.php) HTML, button remained missing from UI despite documentation claiming it was added, see v1.9.127 for actual implementation

- **v1.9.125** (2025-10-08): Fixed Keyboard Settings Button Click Handler - corrected button click handler at [`keyboard.js:53-70`](assets/js/modules/keyboard.js:53-70) to use `addEventListener('click')` instead of `onclick` property assignment, added `type="button"` attribute to prevent form submission, implemented `e.preventDefault()` and `e.stopPropagation()` to prevent event bubbling, added console logging for debugging button clicks, button now reliably navigates to settings page with keyboard tab by updating URL parameters (`view=settings-page&tab=keyboard`) and using `window.location.href`, updated cache-busting version to v1.9.125 for [`keyboard.js`](index.php:29)

- **v1.9.124** (2025-10-08): Changed Keyboard Settings Button to Cog Icon - replaced lightning bolt auto-show toggle button with settings cog icon in virtual keyboard controls at [`keyboard.js:53-63`](assets/js/modules/keyboard.js:53-63), button now uses `fa-cog` icon instead of `fa-bolt`/`fa-bolt-slash`, clicking navigates to settings page with keyboard tab via [`router.navigate('settings-page?tab=keyboard')`](assets/js/modules/keyboard.js:61), provides direct access to keyboard configuration including enable/disable and auto-show settings, removed obsolete [`toggleAutoShow()`](assets/js/modules/keyboard.js:295-352) and [`updateAutoShowButton()`](assets/js/modules/keyboard.js:354-377) methods no longer needed for toggle functionality, simplified user workflow by directing to settings page instead of inline toggle, updated cache-busting version to v1.9.124 for [`keyboard.js`](index.php:29) and system version in [`index.php:20`](index.php:20)

- **v1.9.122** (2025-10-08): Fixed Critical User API Action Parameter Reading - identified and resolved root cause why user operations (create, update, delete) were failing in [`api/users.php:18-33`](api/users.php:18-33), original code at line 19-20 only checked `$_GET['action']` and `$_POST['action']` but when JavaScript sends JSON via `fetch()` with `Content-Type: application/json`, the action parameter is in the JSON request body not $_POST array, implemented proper three-tier action detection: 1) check $_GET parameters first, 2) check $_POST parameters second, 3) decode JSON body via `json_decode(file_get_contents('php://input'), true)` and extract action from JSON data, all user CRUD operations now work correctly because API can properly identify whether request is for list/get/create/update/delete action, this was the critical missing piece preventing user management functionality from working

- **v1.9.121** (2025-10-08): Fixed User Delete Functionality with Loading States and Toast Messages - enhanced [`deleteUser()`](assets/js/modules/admin/users.js:322-367) method to capture delete button reference via event.target, shows spinner icon on button during async delete operation, button disabled during operation to prevent double-clicks, implemented comprehensive message extraction checking `result.data?.message`, `result.message`, and fallback to default "User deleted successfully" message resolving empty toast notification issue, added try-catch-finally error handling that restores original button HTML on error, delete operations now work correctly with proper visual feedback addressing user reports of no spinner during delete progress and empty toast messages, users deleted successfully from WordPress via `wp_delete_user()` function with content reassignment

- **v1.9.120** (2025-10-08): Fixed User Save Button Loading Indicator - added missing `id="user-dialog-save"` attribute to save button in user dialog modal at [`index.php:1587`](index.php:1587), JavaScript code in [`users.js:239`](assets/js/modules/admin/users.js:239) was correctly looking for this element ID but HTML button lacked the attribute causing `saveBtn` variable to be null, loading state implementation at [`users.js:244-246`](assets/js/modules/admin/users.js:244-246) now properly displays spinner icon and "Saving..." text during user creation/update operations, button correctly disables during save preventing double-submission, restores original "Save User" text after operation completes in finally block at [`users.js:312-315`](assets/js/modules/admin/users.js:312-315), provides clear visual feedback to users that save operation is in progress addressing user report of no indication when saving users

- **v1.9.119** (2025-10-08): Added Complete User Management System - implemented comprehensive user administration page accessible via new "Users" menu item in sidebar, provides full CRUD operations for WordPress user accounts with search/filter capabilities, **User List Features**: displays all WordPress users in table format with Name (display name + username), Email, Roles, Registered date, and Actions columns, real-time search by username/email/display name with 300ms debounce, role filter dropdown populated from existing roles system, refresh button to reload user data, **Create User**: modal dialog with username (required, unique), email (required, unique, validated), password (required for new users), first name, last name, role assignment via checkbox interface showing all available roles (except administrator cannot be assigned), validates required fields and uniqueness before creation, **Edit User**: loads existing user data into same modal, username field disabled (cannot change), email can be updated with uniqueness validation, password optional (leave blank to keep current), name fields editable, role management preserves existing role assignments with ability to add/remove roles, **Delete User**: confirmation dialog before deletion, prevents deleting current user or administrators, reassigns deleted user's content to current admin, **Safety Features**: administrator users cannot be edited or deleted, users cannot delete their own account, all operations require manage_options capability, comprehensive input sanitization and validation, **API Implementation**: new endpoint [`api/users.php`](api/users.php:1) with actions: list (get all users with search/role filters), get (single user details), create (new user with role assignment), update (modify user data and roles), delete (remove user with content reassignment), all requests secured with WordPress authentication and authorization checks, **Frontend Module**: [`assets/js/modules/admin/users.js`](assets/js/modules/admin/users.js:1) UsersManager class handles all UI interactions and API communication, integrates with existing StateManager and UIHelpers, modal dialog management for create/edit, async data loading with proper error handling, renders user list with action buttons for edit/delete, **Routing Integration**: added users-page to valid views in [`routing.js:18`](assets/js/modules/routing.js:18), menu button mapping for menu-button-users, loadUsersPage function loads role filter options and user data, **Main.js Integration**: initialized UsersManager in [`main.js:38`](assets/js/main.js:38), exposed global window.usersManager, window.editUser() and window.deleteUser() for onclick handlers, window.loadUsersPage() for routing system, added menu navigation for users button, refresh button handler, setupEventListeners() call for users page, **UI Components**: new users-page section in [`index.php:1012-1050`](index.php:1012-1050) with header (search, role filter, create button, refresh), table headers for organized data display, users-list container populated dynamically, user-dialog modal at [`index.php:1450-1538`](index.php:1450-1538) with form fields for all user properties, roles-list scrollable checkbox area, save/cancel buttons, **Version Updates**: incremented to v1.9.119 in [`index.php:20`](index.php:20), updated users.js to v1.9.119 at [`index.php:50`](index.php:50), updated main.js to v1.9.119 at [`index.php:53`](index.php:53), updated system version in agents.md at line 1859, **Architecture**: follows established modular pattern with manager class, integrates seamlessly with existing role management system, uses WordPress native user functions (wp_create_user, wp_update_user, wp_delete_user), maintains consistency with existing UI design patterns, proper separation of concerns between API/frontend/UI layers

- **v1.9.118** (2025-10-08): Fixed Predefined Roles Editability - corrected role editability logic in [`api/wp-roles-setup.php:57-58`](api/wp-roles-setup.php:57-58) from `!in_array($role_slug, array_merge($predefined_roles, ['administrator']))` to `$role_slug !== 'administrator'`, ensures predefined POS roles (Manager, Cashier, Storekeeper) show Edit buttons and can have their capabilities modified, changed `$always_show_roles` from merging predefined and system roles to only `$system_roles` (Administrator and Shop Manager) at line 35, predefined roles now only appear when actually installed, all roles except Administrator are editable including Shop Manager and all predefined POS roles, provides flexibility to customize predefined role capabilities while keeping Administrator fully protected, updated cache-busting version to v1.9.118 for [`settings.js`](index.php:49)

- **v1.9.117** (2025-10-08): Streamlined Roles Display to Management Roles Only - refined roles list in [`api/wp-roles-setup.php:25-64`](api/wp-roles-setup.php:25-64) to only show Administrator and Shop Manager from system roles, excluded customer, subscriber, and other non-management roles (editor, author, contributor) for cleaner focused interface, changed `$system_roles` array to only include `['administrator', 'shop_manager']`, made Shop Manager editable for POS capability assignment by setting `is_editable = true` for all roles except Administrator and predefined POS roles, added `is_editable` and `is_protected` flags to role data, updated frontend button logic in [`assets/js/modules/admin/settings.js:381-397`](assets/js/modules/admin/settings.js:381-397) to show Edit button only for editable roles, show Delete button only for custom POS roles (not WooCommerce or WordPress roles), display "Protected" label for Administrator, Shop Manager can now have POS capabilities added/removed through edit interface just like custom roles, provides focused management interface showing only roles relevant to POS operations, updated cache-busting version to v1.9.117 for [`settings.js`](index.php:49)

- **v1.9.116** (2025-10-08): Fixed Roles List to Always Show WordPress and WooCommerce Roles - modified roles list logic in [`api/wp-roles-setup.php:25-64`](api/wp-roles-setup.php:25-64) to always include WordPress core roles (Administrator, Editor, Author, Contributor, Subscriber) and WooCommerce roles (Shop Manager, Shop Worker, Customer) regardless of whether POS roles are installed, changed inclusion criteria from "only show roles with wppos_ prefix or wppos capabilities" to "always show predefined WordPress/WooCommerce roles OR custom POS roles OR roles with POS capabilities assigned", created `$always_show_roles` array merging predefined, WooCommerce, and WordPress role slugs, roles list now populates immediately on page load showing all relevant roles for capability assignment, eliminates confusion where users had to install POS roles first before seeing existing WordPress/WooCommerce roles they wanted to modify, provides immediate visibility into complete role structure of WordPress installation, updated cache-busting version to v1.9.116 for [`settings.js`](index.php:49)

- **v1.9.115** (2025-10-08): Added Uninstall Roles Functionality - implemented complete removal system for POS roles and capabilities alongside existing Reinstall functionality, added new `uninstall` action in [`api/wp-roles-setup.php:224-259`](api/wp-roles-setup.php:224-259) that removes all three predefined POS roles (Manager, Cashier, Storekeeper), iterates through ALL WordPress roles to remove POS capabilities from every role including Administrator and Shop Manager, deletes `wppos_capabilities` option from database for complete cleanup, added red "Uninstall" button next to "Reinstall" button in roles management UI at [`assets/js/modules/admin/settings.js:176-184`](assets/js/modules/admin/settings.js:176-184), new [`uninstallRoles()`](assets/js/modules/admin/settings.js:273-299) method with prominent warning confirmation dialog explaining permanent removal and lost user access, shows loading state during uninstall process with spinner, automatically refreshes UI after successful uninstall showing "Install Roles" button, provides error handling with user-friendly messages, use cases include clean uninstall before plugin removal, fresh start configuration, permission reset, and testing during development, updated version from v1.9.114 to v1.9.115 in [`index.php:20`](index.php:20) and cache-busting version for [`settings.js`](index.php:49)

- **v1.9.114** (2025-10-08): Fixed Accordion Button Form Validation Error - added `type="button"` attribute to predefined roles templates accordion button at [`index.php:842`](index.php:842), prevents button from defaulting to type="submit" which was triggering HTML5 form validation on hidden create role dialog fields (role_name, role_slug) marked as required, accordion now toggles content without attempting form submission or showing "An invalid form control with name='role_name' is not focusable" validation errors, updated cache-busting version to v1.9.114 for [`settings.js`](index.php:49)

- **v1.9.112** (2025-10-08): Fixed Settings Form Not Loading Input Values - resolved critical bug where settings form showed empty input fields despite saved values existing in database, root cause was [`settings.js:828-829`](assets/js/modules/admin/settings.js:828-829) setting `window.populateSettingsForm = null` and `window.saveSettings = null` which overwrote correct function assignments made in [`main.js:62`](assets/js/main.js:62), removed problematic null assignments from settings.js, settings form now properly populates all input fields (store name, email, phone, address, receipt footer messages, virtual keyboard checkboxes) when navigating to settings page, updated cache-busting version to v1.9.112 for [`settings.js`](index.php:49)

- **v1.9.111** (2025-10-08): Improved Predefined Templates UI Spacing - removed "Available templates:" label text at [`index.php:860`](index.php:860) and added mt-3 (12px) top margin to role status container at [`index.php:859`](index.php:859) to prevent green "Installed" status badges from being too close to accordion content top edge, provides better visual breathing room in predefined templates section

- **v1.9.110** (2025-10-08): Fixed Predefined Role Templates Accordion Button Padding - changed accordion button padding from p-2 (8px) to p-4 (16px) at [`index.php:842`](index.php:842) to match consistent padding used throughout settings page containers, provides uniform visual design and better button clickability

- **v1.9.109** (2025-10-08): Fixed Settings Page Not Loading Input Values and Added URL Parameter Persistence - resolved critical issue where settings page showed empty input fields instead of current saved values, root cause was settings never being loaded into application state during page initialization, modified [`populateSettingsForm()`](assets/js/modules/admin/settings.js:54-114) to call `await this.loadReceiptSettings()` at line 56 before populating form fields ensuring fresh data from API, settings now properly populate all input fields (store name, email, phone, address, receipt footer messages, virtual keyboard checkboxes) when navigating to settings page, implemented URL parameter persistence for settings tabs (receipt, keyboard, general, roles) via [`initSettingsTabs()`](assets/js/modules/admin/settings.js:696-756) allowing tab state to persist across page reloads and enabling direct links to specific settings sections, updated cache-busting version to v1.9.109 for [`settings.js`](index.php:49)

- **v1.9.103** (2025-10-08): Enhanced Role Management UI/UX with 3-Column Grid Layout and Keyboard Auto-Show Toggle - completely reorganized roles interface in settings page with professional 3-column grid layout at [`index.php:824-953`](index.php:824-953) where role list occupies 2/3 width (8 columns) and creation form appears as sticky sidebar panel occupying 1/3 width (4 columns), added gradient header banner explaining role management, "New Role" button in role list header shows/hides creation form on demand, improved visual hierarchy with icons, better spacing, and color coding, condensed Quick Start predefined roles section into collapsible cards for space efficiency, creation form sticky on right side (`sticky top-4`) for always-accessible role creation, implemented show/hide functionality in [`settings.js:400-440`](assets/js/modules/admin/settings.js:400-440) with buttons triggering form visibility, automatic form hiding on mobile screens after successful role creation, form clears and resets when hidden, added keyboard auto-show toggle button directly in virtual keyboard interface controls at [`keyboard.js:49-66`](assets/js/modules/keyboard.js:49-66) with lightning bolt icon that changes to yellow (`text-yellow-400`) when enabled, toggle button calls [`toggleAutoShow()`](assets/js/modules/keyboard.js:279-289) method which saves setting to backend via settings API and re-initializes auto-show behavior, [`updateAutoShowButton()`](assets/js/modules/keyboard.js:291-300) updates visual state based on current setting, provides immediate visual feedback on auto-show status directly in keyboard UI, updated cache-busting versions to v1.9.103 for [`keyboard.js`](index.php:29), [`settings.js`](index.php:49), and CSS styles, roles management now provides efficient space utilization with professional information architecture and easy-to-access creation workflow

- **v1.9.104** (2025-10-08): Redesigned Role Management with Dialog Interface and Better Organization - completely redesigned roles UI at [`index.php:824-1017`](index.php:824-1017) with cleaner single-column layout instead of complex 3-column grid, renamed "Quick Start Helper" to "Predefined Role Templates" and moved to top of page for better information architecture, role creation now uses modal dialog overlay instead of sidebar panel providing better focus and accessibility, "Create Custom Role" button in header opens dialog at [`index.php:969-1017`](index.php:969-1017) with full-screen overlay and smooth animations, dialog includes overlay click-to-close, escape key support, and form reset on open/close, custom roles list displayed in dedicated section with loading states and empty states, improved visual hierarchy with better spacing and consistent card design across templates and custom roles, templates section now collapsible showing enhanced role cards with capability counts (All Capabilities, 6 Capabilities, 4 Capabilities) instead of verbose listings, removed sticky sidebar and mobile-specific hide/show logic for simpler responsive design, updated JavaScript handlers in [`settings.js:375-449,486-496`](assets/js/modules/admin/settings.js:375-449) to work with dialog system (show/hide dialog buttons, overlay click detection, form submission closes dialog automatically, cancel button functionality), maintains keyboard auto-show toggle functionality from v1.9.103, updated cache-busting version to v1.9.104 for [`settings.js`](index.php:49), provides cleaner UX with modal-focused workflow instead of always-visible sidebar panel

- **v1.9.101** (2025-10-08): Fixed Checkout 403 Error Caused by Nonce Name Mismatch - resolved critical bug preventing all checkout transactions where nonce verification was failing at [`api/checkout.php:23`](api/checkout.php:23), root cause was mismatch between nonce generation in [`index.php:264`](index.php:264) using `wppos_checkout_nonce` and verification in checkout.php using `jpos_checkout_nonce`, corrected checkout.php to use `wppos_checkout_nonce` matching the generated nonce name, checkout now processes successfully after fixing nonce verification, also fixed settings page loading issue where input fields showed empty values - added `await settingsManager.loadReceiptSettings()` at [`main.js:40`](assets/js/main.js:40) to load settings into state during initialization, settings now properly populate via [`populateSettingsForm()`](assets/js/modules/admin/settings.js:54-89), completed RBAC infrastructure with capability checking methods [`userCan()`](assets/js/modules/auth.js:351-361), menu visibility restrictions via [`applyRBACRestrictions()`](assets/js/main.js:146-221), WordPress administrators and shop_manager bypass all restrictions with full access, updated cache-busting versions to v1.9.101 for [`checkout.js`](index.php:37), [`settings.js`](index.php:49), and [`main.js`](index.php:53)

- **v1.9.91** (2025-10-08): Fixed Settings Form Loading & Added RBAC UI Restrictions with Admin Bypass - resolved critical bug where settings form showed empty input fields despite saved values existing, modified [`populateSettingsForm()`](assets/js/modules/admin/settings.js:54-89) to reload settings from API via [`loadReceiptSettings()`](assets/js/modules/admin/settings.js:14-49) before populating form ensuring fresh data, added comprehensive RBAC frontend integration with capability checking methods [`userCan()`](assets/js/modules/auth.js:351-361), [`userCanAny()`](assets/js/modules/auth.js:368-370), [`userCanAll()`](assets/js/modules/auth.js:377-379), [`hasRole()`](assets/js/modules/auth.js:402-405) to [`AuthManager`](assets/js/modules/auth.js:7-406) class with global wrapper functions, implemented menu visibility restrictions via [`applyRBACRestrictions()`](assets/js/main.js:146-187) function that hides Reports/Sessions/Settings/Products menu items based on capabilities, **WordPress administrators and shop_manager role automatically bypass all restrictions and see everything** via early return check at [`main.js:148-152`](assets/js/main.js:148-152), restrictions applied after successful login in [`loadFullApp()`](assets/js/modules/auth.js:249-310) and during event setup in [`setupAllEventListeners()`](assets/js/main.js:185-516), updated cache-busting versions to v1.9.91 for [`auth.js`](index.php:28), [`settings.js`](index.php:49), and [`main.js`](index.php:53)

- **v1.9.74** (2025-10-07): Fixed Settings Page Not Loading Input Values and Saved Settings - resolved critical issue where settings page showed empty input fields instead of current saved values, root cause was settings never being loaded into application state during initialization at [`main.js:41`](assets/js/main.js:41), added `await settingsManager.loadReceiptSettings()` to load settings from API into state immediately after SettingsManager initialization, settings now properly populate all input fields (store name, email, phone, address, receipt footer messages, virtual keyboard checkboxes) when navigating to settings page via [`populateSettingsForm()`](assets/js/modules/admin/settings.js:54-89) exposed at [`main.js:59`](assets/js/main.js:59), updated cache-busting versions to v1.9.74 for [`settings.js`](index.php:49) and [`main.js`](index.php:53)

- **v1.9.73** (2025-10-07): Fixed Virtual Keyboard Auto-Show Implementation - resolved critical issue where virtual keyboard auto-show setting was not working due to missing [`initKeyboardAutoShow()`](assets/js/modules/keyboard.js:207) function referenced but never implemented in codebase, added comprehensive auto-show functionality in [`keyboard.js:207-258`](assets/js/modules/keyboard.js:207) with focus/blur listener management for all text/email/search inputs excluding product editor and fee/discount modals, implemented [`initAutoShow()`](assets/js/modules/keyboard.js:207) method that checks settings and attaches listeners when both `virtual_keyboard_enabled` and `virtual_keyboard_auto_show` are true, added [`removeAutoShowListeners()`](assets/js/modules/keyboard.js:249) for proper cleanup, exposed global [`window.initKeyboardAutoShow()`](assets/js/modules/keyboard.js:263) helper function called by [`settings.js:24,46,76,172`](assets/js/modules/admin/settings.js:24) after loading/saving settings, corrected API endpoint paths in [`settings.js:16,150`](assets/js/modules/admin/settings.js:16) from absolute `/jpos/api/settings.php` to relative `api/settings.php` for proper routing, fixed incorrect element IDs in [`settings.js:133-134`](assets/js/modules/admin/settings.js:133) from `enable-virtual-keyboard`/`auto-show-keyboard` to correct `setting-keyboard-enabled`/`setting-keyboard-auto-show` matching HTML checkboxes in [`index.php:780,786`](index.php:780), updated cache-busting versions to v1.9.73 for [`keyboard.js`](index.php:29) and [`settings.js`](index.php:49), virtual keyboard settings now save correctly to database via [`api/settings.php:66-71`](api/settings.php:66-71), auto-show functionality properly initializes on app load and after settings changes, keyboard appears automatically when clicking input fields when enabled

- **v1.9.72** (2025-10-07): Completed JavaScript Modularization Refactoring - successfully finished comprehensive refactoring project that transformed monolithic 4,997-line [`main.js`](assets/js/main.js:1) into modular architecture with 14 focused modules, reduced main.js by 90.7% to 466 lines functioning as pure orchestrator coordinating manager instances and event delegation, created modules: [`state.js`](assets/js/modules/state.js:1) (219 lines centralized state management), [`routing.js`](assets/js/modules/routing.js:1) (227 lines URL-based navigation), [`ui-helpers.js`](assets/js/modules/core/ui-helpers.js:1) (229 lines shared utilities), [`auth.js`](assets/js/modules/auth.js:1) (265 lines authentication), [`keyboard.js`](assets/js/modules/keyboard.js:1) (217 lines virtual keyboard), [`drawer.js`](assets/js/modules/financial/drawer.js:1) (217 lines cash drawer), [`cart.js`](assets/js/modules/cart/cart.js:1) (462 lines cart operations), [`checkout.js`](assets/js/modules/cart/checkout.js:1) (418 lines payment processing), [`held-carts.js`](assets/js/modules/cart/held-carts.js:1) (266 lines cart holding), [`products.js`](assets/js/modules/products/products.js:1) (596 lines product display), [`product-editor.js`](assets/js/modules/products/product-editor.js:1) (821 lines product editing), [`orders.js`](assets/js/modules/orders/orders.js:1) (336 lines order management), [`receipts.js`](assets/js/modules/orders/receipts.js:1) (246 lines receipt printing), [`reports.js`](assets/js/modules/financial/reports.js:1) (543 lines reporting), [`settings.js`](assets/js/modules/admin/settings.js:1) (195 lines settings), [`sessions.js`](assets/js/modules/admin/sessions.js:1) (138 lines session history), all modules integrate seamlessly with proper dependency injection pattern passing StateManager and UIHelpers instances, comprehensive testing confirmed zero functional regressions, improved code maintainability enables faster development and easier debugging, updated all script versions to v1.9.72 in [`index.php:20-53`](index.php:20-53), documented in [`docs/REFACTORING_PLAN.md`](docs/REFACTORING_PLAN.md:1) with complete implementation details

- **v1.9.71** (2025-10-07): Removed Customer Attachment Feature - completely removed customer attachment functionality due to insurmountable WooCommerce conflicts where even direct database writes were being overridden by unknown async processes, simplified [`api/checkout.php`](api/checkout.php:44-165) to always use `$current_user->ID` (cashier/admin) as order customer, removed all customer lookup logic from lines 58-83, removed customer-specific address handling from lines 107-161, removed customer_id parameter acceptance from API, all POS orders now consistently show cashier as customer providing clear audit trail in WooCommerce admin, uses store address from settings for all orders, maintains `_jpos_created_by` and `_jpos_created_by_name` meta fields for tracking which cashier processed transaction, eliminates 200+ lines of complex customer detection and address mapping code, more reliable solution since customer is set once at creation with cashier ID and never needs modification, removes entire feature that proved impossible to implement reliably with WooCommerce's aggressive customer detection and background processing

- **v1.9.70** (2025-10-07): Direct Database Customer Assignment (deprecated - feature removed in v1.9.71) - attempted direct database writes at 5 critical points, proved insufficient against unknown WooCommerce background processes that continued overriding customer assignments even after HTTP response completed

- **v1.9.69** (2025-10-07): Hook-Level Customer Assignment Protection (deprecated - replaced by v1.9.70) - attempted comprehensive WooCommerce hook protection system, created [`api/checkout-customer-fix.php`](api/checkout-customer-fix.php:1) with hook interceptions for `woocommerce_checkout_customer_id`, `woocommerce_order_status_changed`, and `woocommerce_order_after_calculate_totals`, proved insufficient against aggressive WooCommerce hook chain that continued to override customer assignments

- **v1.9.68** (2025-10-07): Multiple Customer ID Confirmation Points - implemented defensive programming strategy to ensure customer assignment persists through WooCommerce's internal processes at [`api/checkout.php:88-212`](api/checkout.php:88-212), changed approach based on WooCommerce best practices: creates order without customer parameter using `wc_create_order(['status' => 'pending'])` (line 89), then explicitly calls `$order->set_customer_id($order_customer_id)` at three critical points: 1) immediately after order creation (line 97) to set initial customer, 2) before first save after `calculate_totals()` (line 195) because WooCommerce sometimes resets customer_id during total calculations, 3) before final save after `set_status('completed')` (line 207) because status change hooks can override customer assignment, comprehensive debug logging at each confirmation point (lines 99, 202, 212) tracks customer ID through entire process, prevents WooCommerce from auto-detecting and using logged-in admin user, ensures correct customer (either attached customer or cashier for walk-ins) appears in WooCommerce admin order details, addresses hook interference issues by re-confirming customer at every point where WooCommerce might reset it

- **v1.9.67** (2025-10-07): Restored Customer Attachment with Proper WooCommerce Method - with proper `wc_create_order()` function now in place (from v1.9.66), restored complete customer attachment functionality at [`api/checkout.php:58-146`](api/checkout.php:58-146), supports two workflows: 1) customer attached via "Attach Customer" button - uses customer's WordPress user ID and billing/shipping addresses from user meta, or 2) walk-in sale without customer - uses cashier's user ID and store address from settings, determines `order_customer_id` before order creation (lines 61-86): checks if `$customer_id` provided, looks up customer with `get_userdata()`, uses customer ID if found or falls back to `$current_user->ID`, passes determined customer_id to `wc_create_order(['customer_id' => $order_customer_id])` ensuring proper assignment from initialization, stores customer metadata `_jpos_customer_id` and `_jpos_customer_name` when customer attached (lines 100-103), maintains separate cashier audit trail via `_jpos_created_by` meta (lines 106-107), conditional address handling (lines 110-146): customer address from WordPress user meta when attached, store address from settings for walk-in sales, comprehensive debug logging tracks customer lookup and assignment, no WooCommerce hook conflicts because customer set at creation time via official API, customer attachment now works reliably with both attached customers showing in WooCommerce admin and walk-in sales showing cashier as customer

- **v1.9.66** (2025-10-07): Use Proper WooCommerce Order Creation Function - identified root cause of customer assignment issues at [`api/checkout.php:58-77`](api/checkout.php:58-77) where `new WC_Order()` was being used instead of proper `wc_create_order()` function, when using manual instantiation WooCommerce hooks during save were overriding customer_id with logged-in user, replaced with `wc_create_order(['customer_id' => $current_user->ID, 'status' => 'pending'])` which properly initializes order with specified customer from the start, matches pattern already working in refund.php line 80, removed redundant `set_customer_id()` and `update_meta_data('_customer_user')` calls since `wc_create_order()` handles this correctly, added error checking with `is_wp_error()`, simplified save logic to: create order with customer → calculate totals → save → set completed status → save, eliminates WooCommerce hook conflicts by using official API method, customer now definitively assigned correctly without manual database manipulation

- **v1.9.65** (2025-10-07): Simplified Customer Assignment to Current Admin - completely removed customer attachment complexity after persistent issues with guest checkout and WooCommerce hook interference, reverted to straightforward approach at [`api/checkout.php:61-97`](api/checkout.php:61-97) where ALL POS orders are assigned to current logged-in admin/cashier user (`$current_user->ID`), removed entire customer lookup logic (lines 65-89), removed conditional address handling (lines 109-152), now uses store address for all POS sales from settings, removed database forcing logic and cache clearing (lines 217-260), simplified to basic WooCommerce order creation: set customer_id, calculate totals, save, set status completed, save again, eliminates all complexity around guest checkout vs customer attachment vs admin assignment, provides reliable and consistent behavior where cashier is always the order customer in WooCommerce, maintains backward compatibility with existing `_jpos_created_by` audit trail meta

- **v1.9.64** (2025-10-07): Force Guest Checkout Persistence Against WooCommerce Hooks - resolved critical bug where guest orders (no customer attached) were still showing admin/cashier as customer in WooCommerce despite initial fix in v1.9.63, root cause identified at [`api/checkout.php:236-260`](api/checkout.php:236-260) where conditional `if ($customer_id && $order_customer_id > 0)` only forced database updates for attached customers, WooCommerce `set_status()` hooks were running after order save and setting `post_author` to current logged-in user (admin) for guest orders, removed conditional check and now ALWAYS forces correct `customer_id` value regardless of whether it's 0 (guest) or actual customer ID, uses direct `$wpdb->update()` to set `wp_posts.post_author` and `update_post_meta()` to set `_customer_user` meta field after status hooks complete, added `clean_post_cache()` to clear WooCommerce caches, guest orders now definitively stay as guest (customer_id = 0) in WooCommerce admin and don't get admin user incorrectly assigned, maintains proper audit trail via `_jpos_created_by` meta while keeping customer field accurate

- **v1.9.63** (2025-10-07): Use Guest Checkout Instead of Admin When No Customer Attached - changed customer attachment behavior at [`api/checkout.php:63`](api/checkout.php:63) to default to guest checkout (customer_id = 0) instead of using cashier/admin user ID when no customer is explicitly attached to the cart, prevents walk-in sales from incorrectly appearing as admin orders in WooCommerce admin panel, customer_id is now only set when a customer is actually selected via "Attach Customer" button in POS interface, modified conditional logic at [`api/checkout.php:238-259`](api/checkout.php:238-259) to only force customer persistence via direct database updates when `$customer_id > 0` (actual customer attached) rather than for all orders, maintains proper separation between cashier tracking (via `_jpos_created_by` and `_jpos_created_by_name` meta fields) and customer assignment (via `customer_id` and `_customer_user`), guest orders now display correctly in WooCommerce with no assigned customer while audit trail still records which cashier processed the transaction, provides cleaner order management and accurate customer data in WooCommerce

- **v1.9.62** (2025-10-07): Fixed Customer Attachment Order of Operations - resolved critical bug where attached customers were not appearing in WooCommerce admin order details at [`api/checkout.php:214-249`](api/checkout.php:214-249), root cause was WooCommerce `set_status('completed')` triggering hooks that reset the customer_id back to cashier/admin user, reordered checkout operations to save order BEFORE setting status: 1) calculate totals, 2) set customer_id and _customer_user meta, 3) save order with customer, 4) THEN set status to completed and save again, 5) force customer persistence after status hooks run by directly updating `wp_posts.post_author` via `$wpdb->update()` and `_customer_user` meta via `update_post_meta()`, added `clean_post_cache($order_id)` to clear WooCommerce caches ensuring admin displays refreshed data, customer now correctly shows in WooCommerce → Orders → Order Details as the order customer instead of showing cashier/admin user, billing/shipping addresses still correctly use customer's saved addresses from WordPress user meta when customer attached

- **v1.9.61** (2025-10-07): Added Bulk Delete for Orders - implemented comprehensive bulk deletion system for orders page with single "Bulk Actions" button in header (space-efficient design) at [`index.php:520`](index.php:520) that opens modal for action selection, button displays "(X)" count when orders selected and disabled when none selected, checkbox column in orders table grid at [`index.php:565-567`](index.php:565-567) with col-span-1 for selection, "Select All" checkbox in table header at [`index.php:559`](index.php:559) toggles all selections with proper indeterminate state handling, bulk actions modal at [`index.php:1255-1269`](index.php:1255-1269) shows selected count and offers "Delete Without Restoring Stock" and "Delete and Restore Stock" action buttons, implemented in [`OrdersManager`](assets/js/modules/orders/orders.js:1-720) class with `selectedOrders` Set data structure for tracking selections, [`setupBulkActions()`](assets/js/modules/orders/orders.js:18-58) method wires all event listeners including button click to [`openBulkActionsModal()`](assets/js/modules/orders/orders.js:594-608), [`handleSelectAll()`](assets/js/modules/orders/orders.js:560-572) toggles all checkboxes, [`updateBulkActionUI()`](assets/js/modules/orders/orders.js:577-592) updates button text/state and select all checkbox indeterminate property, [`executeBulkDelete()`](assets/js/modules/orders/orders.js:613-720) async method iterates selected order IDs calling existing [`api/delete-order.php`](api/delete-order.php:1) endpoint sequentially with restore_stock boolean parameter, tracks success/error counts separately, displays summary toast notifications ("X order(s) deleted", "Y order(s) failed"), clears `selectedOrders` Set, refreshes orders list via [`fetchOrders()`](assets/js/modules/orders/orders.js:87-107), optionally refreshes products list when stock restored, provides efficient batch order management without cluttering header interface

- **v1.9.60** (2025-10-07): Fixed Customer Attachment in WooCommerce Orders - resolved critical issue where customers attached to orders via POS were not appearing in WooCommerce admin order area, modified [`api/checkout.php:88-133`](api/checkout.php:88-133) to conditionally use customer's actual billing/shipping address when customer is attached instead of always overwriting with store address for all orders, when `customer_id` is provided the system now fetches customer data from WordPress user meta using `get_user_meta()` for billing/shipping fields (first_name, last_name, company, address_1/2, city, state, postcode, country, email, phone), order's `customer_id` is set to the attached customer (not the cashier), WooCommerce admin now properly displays customer information in order details allowing proper customer management and communication, cashier is still tracked separately via `_jpos_created_by` meta for audit purposes, walk-in sales without attached customer continue to use store address as before

- **v1.9.47** (2025-10-07): Order Deletion API Response Fix - fixed critical bug in order deletion where delete buttons showed "Deleting..." but then displayed empty toast messages with no actual deletion or stock restoration occurring at [`orders.js:208-244`](assets/js/modules/orders/orders.js:208-244), root cause was incorrect response parsing that didn't account for WordPress `wp_send_json_success()` wrapping data in `result.data` property, corrected message extraction to check both `result.data?.message` and `result.message` with fallback to default message, added comprehensive error logging with `console.log('Delete order response:', result)` and `console.error('Delete response error:', errorText)` for debugging, improved HTTP error handling to capture and log response text before throwing error, modal now properly closes after successful deletion, orders list refreshes correctly via `fetchOrders()`, toast notifications display appropriate success messages ("Order deleted successfully", "Order deleted and stock restored") or error messages with specific failure details

- **v1.9.46** (2025-10-07): Customer Display on Receipts - enhanced receipt module to show customer name on printed receipts when order has attached customer information at [`receipts.js:187`](assets/js/modules/orders/receipts.js:187), customer display appears in order details section below order number and date, uses conditional rendering `${data.customer_name ? \`<p>Customer: ${data.customer_name}</p>\` : ''}` to only show when customer exists, integrates with customer attachment feature from v1.8.54, provides complete transaction record including who made the purchase, works in both screen display and printed output, change amount display already implemented in v1.9.44

- **v1.9.45** (2025-10-07): Order Deletion with Stock Restoration Options & Checkout State Fix - implemented complete order deletion system with DELETE HTTP method handler in [`api/orders.php:16-58`](api/orders.php:16-58) that accepts order_id and restore_stock boolean parameters, added red "Delete" button to every order row in orders table at [`orders.js:111`](assets/js/modules/orders/orders.js:111), created confirmation modal at [`index.php:1216-1233`](index.php:1216-1233) with two distinct action buttons offering choice between deleting order permanently or deleting while restoring inventory levels, implemented [`OrdersManager.openDeleteOrderModal()`](assets/js/modules/orders/orders.js:158-195) to display order number and handle button events, implemented [`OrdersManager.deleteOrder()`](assets/js/modules/orders/orders.js:196-237) async method that sends DELETE request to API with selected stock restoration option, stock restoration logic in API iterates through order items and increases product stock quantities using `wc_get_product()` and `set_stock_quantity()`, uses `wp_delete_post($order_id, true)` for permanent deletion bypassing trash, displays success/error toasts and refreshes orders list after deletion, provides administrators full control over order cleanup with proper inventory management; ALSO fixed checkout error "Cannot read properties of undefined (reading 'items')" by correcting state access in [`checkout.js:323`](assets/js/modules/cart/checkout.js:323) from direct property `this.state.drawer.isOpen` to proper StateManager method `this.state.getState('drawer.isOpen')`, ensures proper state management pattern throughout modularized codebase

- **v1.9.44** (2025-10-07): Change Display in Receipts - added change calculation and display to printed receipts at [`ReceiptsManager.showReceipt()`](assets/js/modules/orders/receipts.js:128-167), calculates total paid amount from split_payments array or single payment, computes change as `totalPaid - orderTotal`, displays "Change: $X.XX" in bold when change > 0 (customer overpaid), suppresses display when payment exactly matches total (no change due), integrates into receipt payment section after payment method details, provides customers with clear record of change received

- **v1.9.43** (2025-10-07): Blue Change Color in Checkout Dialog - changed Change text color from green (text-green-400) to blue (text-blue-400) in checkout modal at [`index.php:1174`](index.php:1174) and [`CheckoutManager.updateTotal()`](assets/js/modules/cart/checkout.js:274), provides better visual distinction between Change (blue) and Fee amounts (green), follows POS industry convention where change/cash back typically displays in blue or cyan, Remaining amount still displays in red when payment is insufficient

- **v1.9.42** (2025-10-07): Complete Checkout Dialog with Payment Tracking - added Amount Paid and Change/Remaining rows to checkout modal breakdown at [`index.php:1167-1175`](index.php:1167-1175), displays complete payment flow showing Subtotal → Discount/Fee adjustments → Total → Amount Paid → Change, updated [`CheckoutManager.updateTotal()`](assets/js/modules/cart/checkout.js:214-293) to calculate change as `sum - cartTotal` and dynamically update payment tracking elements (`split-payment-paid`, `split-payment-change`), change display intelligently switches between "Change" (blue text) when customer overpays and "Remaining" (red text) when underpaid, provides real-time feedback as cashier enters split payment amounts showing exactly how much has been tendered and what change is owed or what amount is still needed, completes transparent payment workflow from cart total through payment entry to change calculation

- **v1.9.41** (2025-10-07): Enhanced Checkout Dialog Breakdown Display - implemented comprehensive cart breakdown in checkout modal matching cart display layout with labels on left and values on right at [`index.php:1155-1173`](index.php:1155-1173), shows Subtotal (items only), Discount (if applied, displayed in red with conditional visibility), Fee (if applied, displayed in green with conditional visibility), and Total (bold, emphasized with border separator) in structured breakdown box, updated [`CheckoutManager.updateTotal()`](assets/js/modules/cart/checkout.js:214-273) to populate individual breakdown elements (`split-payment-subtotal`, `split-payment-discount`, `split-payment-fee`, `split-payment-total`) dynamically instead of building HTML string, discount/fee rows automatically hide when not applicable using `classList.add/remove('hidden')`, provides transparent financial breakdown during payment process helping users understand how subtotal + fees - discounts = total, matches cart sidebar display pattern for consistency

- **v1.9.40** (2025-10-07): Improved Checkout Dialog Layout - removed "Total:" label and made total amount full-width centered with larger bold font (text-2xl) at [`index.php:1155-1158`](index.php:1155-1158) for better visibility during payment processing, increased modal width from max-w-md to max-w-2xl at [`index.php:1138`](index.php:1138) for better space utilization, centered "Checkout" heading, provides cleaner and more prominent display of order total in split payment modal without left-aligned label, improves user experience by making the critical payment amount more visible and accessible

- **v1.9.39** (2025-10-07): Fixed Checkout State Access Error - corrected [`CheckoutManager`](assets/js/modules/cart/checkout.js:387-414) class to properly access state properties using [`StateManager.getState()`](assets/js/modules/state.js:134-147) method instead of direct property access, updated 9 locations across [`checkout.js:31-414`](assets/js/modules/cart/checkout.js:31-414) where `this.state` was incorrectly treated as the state object rather than the StateManager instance, replaced `this.state.cart.items` with `this.state.getState('cart.items')`, `this.state.fee` with `this.state.getState('fee')`, `this.state.discount` with `this.state.getState('discount')`, `this.state.drawer.isOpen` with `this.state.getState('drawer.isOpen')`, `this.state.nonces.*` with `this.state.getState('nonces.*')`, and `this.state.returns.fromOrderId` with `this.state.getState('returns.fromOrderId')`, resolves "Uncaught TypeError: Cannot read properties of undefined (reading 'items')" error at [`checkout.js:389:26`](assets/js/modules/cart/checkout.js:389) that prevented split payment modal from opening during checkout, ensures checkout flow works correctly with modularized architecture

- **v1.9.38** (2025-10-07): Clearer Cart Breakdown Display - implemented comprehensive cart totals breakdown showing Subtotal (items only), Discount (if applied), Fee (if applied), and Total (final amount) in [`index.php:421-440`](index.php:421-440), updated HTML structure to separate subtotal display from discount/fee rows with visual hierarchy using borders and emphasis on total, modified [`cart.js:386-550`](assets/js/modules/cart/cart.js:386-550) rendering logic to calculate values separately using `getSubtotal()`, `getDiscounts()`, `getFees()`, and `getTotal()` methods, discount displays in red (`text-red-400`) with "(X%)" or dollar formatting, fee displays in green (`text-green-400`) with "(X%)" or dollar formatting, improved item summary format to "X item(s) (Y unit(s))" for better readability, provides transparent financial breakdown helping users understand how cart total is calculated from base items through adjustments to final amount

- **v1.9.37** (2025-10-07): Dynamic padding and placeholder for percentage mode - implemented conditional styling in [`cart.js:114-154`](assets/js/modules/cart/cart.js:114-154) that only adds extra right padding (`pr-8 pl-2`) when percentage tab is selected, flat mode maintains normal padding (`px-2`), placeholder changes from "0.00" for flat amounts to "00" for whole number percentages, modal open functions [`applyFee()`](assets/js/modules/cart/cart.js:680-712) and [`applyDiscount()`](assets/js/modules/cart/cart.js:716-748) reset to flat mode styling, provides cleaner UX with appropriate spacing and placeholders for each mode, updated cache-busting version from v1.9.36 to v1.9.37 in [`index.php:20,36`](index.php:20,36)

- **v1.9.36** (2025-10-07): Fixed percentage symbol padding to prevent number overlap - changed input field padding from `px-2` to `pl-2 pr-8` in [`index.php:464`](index.php:464) to add extra right padding (32px) that prevents typed numbers from overlapping with the "%" symbol positioned at right side, maintains left padding at 8px and increases right padding to accommodate symbol plus margin, ensures clean visual separation between input text and percentage indicator, updated cache-busting version from v1.9.35 to v1.9.36 in [`index.php:20,36`](index.php:20,36)

- **v1.9.35** (2025-10-07): Visual "%" symbol appears next to input field when percentage mode is selected - added percentage symbol span in [`index.php:465`](index.php:465) with hidden state by default and absolute positioning on right side of input, implemented show/hide logic in [`cart.js:116-145`](assets/js/modules/cart/cart.js:116-145) triggered when switching between flat and percentage tabs, also updated [`applyFee()`](assets/js/modules/cart/cart.js:678-706) and [`applyDiscount()`](assets/js/modules/cart/cart.js:710-738) to hide symbol when modal opens with flat tab selected, symbol positioned with pointer-events-none to prevent interference with input, enhanced visual feedback showing "%" next to entered value when in percentage mode, updated cache-busting version from v1.9.34 to v1.9.35 in [`index.php:20,36`](index.php:20,36)

- v1.9.25: Removed chart legend display - set `legend.display: false` in Chart.js configuration in [`reports.js:192,297`](assets/js/modules/financial/reports.js:192) to hide all legend indicators, provides cleaner chart presentation focusing on the data visualization, updated version from v1.9.24 to v1.9.25 in [`index.php:46`](index.php:46)

- **v1.9.26** (2025-10-07): Reports API - Added complete period data filling. Modified [`api/reports.php`](api/reports.php:139-201) to generate all periods in date range and fill missing days/hours with zero values, ensuring continuous chart lines across entire period regardless of sales activity - creates better data visualization showing full week/month/year timelines.
- **v1.9.27** (2025-10-07): Customer search fix - Implemented actual API integration in [`assets/js/modules/cart/cart.js`](assets/js/modules/cart/cart.js:554-619) replacing placeholder `searchCustomers()` function with full API call to [`api/customers.php`](api/customers.php:1), added loading states, error handling, result rendering with click handlers, and fixed `toggleCustomerKeyboard()` to properly show virtual keyboard - customer search now fully functional.
- **v1.9.28** (2025-10-07): Customer search UI improvements - Added rounded corners with `rounded-lg` and smooth `transition-colors` to hover state on customer search results for better visual feedback, removed duplicate "Enter at least 2 characters" message, simplified initial state to show "Start typing to search customers..." in [`assets/js/modules/cart/cart.js`](assets/js/modules/cart/cart.js:540).
- **v1.9.30** (2025-10-07): Fixed fee/discount input - Removed `readonly` and `inputmode="none"` attributes from amount input field in [`index.php`](index.php:465) to allow both physical keyboard typing and on-screen numpad input for entering fee/discount amounts.
- **v1.9.31** (2025-10-07): Fee/discount input validation - Added JavaScript validation in [`assets/js/modules/cart/cart.js`](assets/js/modules/cart/cart.js:29-44) to restrict input to numbers and decimal point only, with automatic filtering of non-numeric characters, single decimal point enforcement, and 2 decimal place limit; added `inputmode="decimal"` to HTML input for better mobile keyboard support.
- v1.9.24: Improved empty state display for sales reports - replaced text-only "No data available" message with placeholder chart showing empty axes and "No data available for this period" title in chart, ensures consistent UI presence even when no orders exist for selected period, maintains proper chart container sizing and prevents layout shifts, updated version from v1.9.23 to v1.9.24 in [`index.php:46`](index.php:46)
- v1.9.23: Fixed reports period selection and API URL - corrected incorrect API URL in [`reports.js:21`](assets/js/modules/financial/reports.js:21) from `/jpos/api/reports.php` to relative path `api/reports.php`, fixed period selection event listeners in [`main.js:362-377`](assets/js/main.js:362-377) to properly call `updateChartPeriod('custom')` when custom date range inputs change, fixed print report modal to show when print button clicked by adding modal display logic, sales reports now correctly respond to period changes updating both chart visualization and orders list, updated cache-busting version from v1.9.13 to v1.9.23 across all modules in [`index.php:20-53`](index.php:20-53)
- v1.8.71: Expanded REFACTORING_PLAN.md with comprehensive implementation details - added detailed documentation for all 10 completed modules (229-336 lines each) with implementation notes, integration points, and issues resolved, comprehensive specifications for 4 remaining modules (cart.js, products.js, reports.js, product-editor.js) totaling ~2,300 lines with complete functionality requirements and extraction sources, detailed Phase 5 integration checklist with 6-hour testing matrix covering core functionality/browser compatibility/performance/error handling, added troubleshooting guide with 6 common issues and solutions, migration guide for developers, best practices section, revised 14-day timeline with weekly breakdown, final deployment checklist with pre/during/post deployment steps, success criteria definitions, support escalation procedures, and future enhancement roadmap - total additions: ~600 lines of actionable documentation to guide remaining modularization work in [`docs/REFACTORING_PLAN.md`](docs/REFACTORING_PLAN.md:471-1070)
- v1.8.70: Unified settings save logic for consistent behavior - simplified [`api/settings.php:53-82`](api/settings.php:53-82) to treat ALL settings equally by comparing complete old vs new settings arrays ($old_settings !== $current_settings), removed complex separate keyboard settings detection logic, now keyboard booleans and receipt text fields use identical change detection mechanism, forces database update only when actual changes detected via PHP array comparison, consistent "Settings saved successfully" / "Settings are unchanged" messages for all field types, updated cache-busting version from v1.8.69 to v1.8.70 in [`index.php:25`](index.php:25)
- v1.8.69: Fixed virtual keyboard settings save logic to properly detect and persist changes - root cause was WordPress's `update_option()` returning false when new value equals old value, causing "Settings are unchanged" message, implemented explicit change detection in [`api/settings.php:64-89`](api/settings.php:64-89) that tracks whether keyboard settings (virtual_keyboard_enabled, virtual_keyboard_auto_show) have changed via strict boolean comparison, when keyboard settings change the function forces database update by passing false as third parameter to `update_option()` to bypass WordPress's value comparison, when only keyboard settings change settings now save successfully, updated cache-busting version from v1.8.68 to v1.8.69 in [`index.php:25`](index.php:25)
- v1.8.68: Fixed virtual keyboard settings persistence and auto-show initialization - root cause was settings API not returning/saving the new keyboard fields and auto-show not being initialized on app load, updated [`api/settings.php:12-22`](api/settings.php:12-22) to include virtual_keyboard_enabled (default true) and virtual_keyboard_auto_show (default false) in [`get_jpos_default_settings()`](api/settings.php:12), added boolean handling at [`api/settings.php:62-67`](api/settings.php:62-67) to save keyboard settings to database, modified [`loadReceiptSettings()`](assets/js/main.js:304-320) to call [`initKeyboardAutoShow()`](assets/js/main.js:3482) after loading settings from API so auto-show is initialized on every app startup not just when viewing settings page, settings now properly persist across page reloads and sessions, auto-show functionality now works correctly on all text/email/search inputs when both settings are enabled (excluding product editor/fee/discount modals), checkboxes now reflect saved state when reopening settings page, updated cache-busting version from v1.8.67 to v1.8.68 in [`index.php:25`](index.php:25)
- v1.8.67: Enhanced virtual keyboard system with comprehensive settings and auto-show functionality - fixed customer dialog close button by changing [`cartManager.hideCustomerSearch()`](index.php:1069) to [`window.hideCustomerSearch()`](index.php:1069), fixed keyboard z-index from z-50 to z-[9999] at [`keyboard.js:29`](assets/js/modules/keyboard.js:29) to appear above all content, added virtual keyboard enable/disable and auto-show settings to settings page at [`index.php:705-719`](index.php:705-719) with two checkboxes for control, implemented [`initKeyboardAutoShow()`](assets/js/main.js:3482) function that attaches focus listeners to all text/email/search inputs when auto-show is enabled (excludes modals like product editor and fee/discount), settings saved via enhanced [`saveSettings()`](assets/js/main.js:3174-3216) and loaded via [`populateSettingsForm()`](assets/js/main.js:3170-3188), keyboard button visibility controlled by enable setting, auto-show respects both enable and auto-show settings, updated cache-busting version from v1.8.66 to v1.8.67 in [`index.php:25`](index.php:25)
- v1.8.66: Fixed virtual keyboard functionality in customer search modal - corrected broken function reference at [`index.php:1080`](index.php:1080) from non-existent `cartManager.toggleKeyboard()` to existing `window.toggleCustomerKeyboard()` helper function, keyboard button in customer search modal now properly triggers virtual keyboard display, uses pre-existing [`OnScreenKeyboard`](assets/js/modules/keyboard.js:7) class with QWERTY layout optimized for customer name/email search, keyboard appears at bottom of screen with touch-friendly keys including Space, Backspace, Clear, @, and . special keys, auto-hides when modal closes, updated cache-busting version from v1.8.65 to v1.8.66 in [`index.php:25`](index.php:25)
- v1.8.65: Hidden receipt dialog scrollbar while maintaining scroll functionality - added CSS rules at [`index.php:33-40`](index.php:33-40) to hide scrollbar across all browsers (`scrollbar-width: none` for Firefox, `-ms-overflow-style: none` for IE/Edge, `::-webkit-scrollbar { display: none }` for Chrome/Safari/Opera), receipt content div at [`index.php:744`](index.php:744) keeps `max-height: 70vh` and `overflow-y: auto` for proper viewport overflow handling, provides clean presentation without visible scrollbar while maintaining scrollable content, updated cache-busting version from v1.8.64 to v1.8.65 in both [`index.php:25`](index.php:25) and [`main.js:1-3`](assets/js/main.js:1-3)
- v1.8.64: Removed receipt dialog scrollbar for cleaner UI - removed `max-height: 70vh` and `overflow-y: auto` inline styles from receipt content container at [`index.php:736`](index.php:736), receipt now displays full height without internal scrolling, improved visual presentation and user experience, updated cache-busting version from v1.8.63 to v1.8.64 in both [`index.php:25`](index.php:25) and [`main.js:1-3`](assets/js/main.js:1-3)
- v1.8.63: Fixed customer filter dropdown z-index hierarchy with parent container - root cause was parent header lacking z-index, added z-20 to orders page header container at [`index.php:457`](index.php:457), maintained z-10 on sticky table headers at [`index.php:511`](index.php:511), and z-[10000] on dropdown at [`index.php:492`](index.php:492), proper stacking hierarchy now: dropdown (10000) > parent header (20) > table headers (10), ensures dropdown always renders above all page elements including sticky headers, updated cache-busting version from v1.8.62 to v1.8.63 in both [`index.php:25`](index.php:25) and [`main.js:1-3`](assets/js/main.js:1-3)
- v1.8.62: Fixed customer filter dropdown z-index positioning - confirmed table headers have z-10 from sticky positioning context, increased dropdown z-index from 9999 to 10000 at [`index.php:492`](index.php:492) and explicitly set z-10 on sticky table headers at [`index.php:511`](index.php:511) to establish proper stacking context, ensures customer search results dropdown appears above all page elements including sticky table headers, resolves visual issue where dropdown was rendering below table content, updated cache-busting version from v1.8.61 to v1.8.62 in both [`index.php:25`](index.php:25) and [`main.js:1-2`](assets/js/main.js:1-2)
- v1.8.61: Fixed customer filter dropdown z-index - increased from z-50 to z-[9999] to appear above sticky table headers
- v1.8.60: Enhanced customer filtering with searchable input field - converted static dropdown to real-time search interface using existing [`api/customers.php`](api/customers.php:1) endpoint, users can search by name or email with minimum 2 characters and 300ms debounce for performance, implemented [`searchCustomersForFilter()`](assets/js/main.js:1543-1560) to fetch results, [`displayCustomerFilterResults()`](assets/js/main.js:1562-1596) to render dropdown, [`selectCustomerForFilter()`](assets/js/main.js:1611-1618) for selection handling, updated UI in [`index.php:482-495`](index.php:482-495) with searchable input field, results dropdown container, and clear button, added event listeners at [`main.js:637-675`](assets/js/main.js:637-675) for input/clear/click-outside, removed old [`populateCustomerFilter()`](assets/js/main.js:1627-1641) dropdown function and its call in [`fetchOrders()`](assets/js/main.js:1641), improves usability and scales better for large customer databases, updated version from v1.8.59 to v1.8.60 in [`index.php`](index.php:25) and [`main.js`](assets/js/main.js:3)
- v1.8.59: Implemented customer filtering in order view page - added customer dropdown filter to orders page header at [`index.php:478`](index.php:478), backend API support in [`orders.php:72-81`](api/orders.php:72-81) with SQL filtering by customer ID via EXISTS subquery checking `_customer_user` meta, customer data now included in all order responses with customer_id and customer_name fields via fallback logic at [`orders.php:100-122`](api/orders.php:100-122) (WordPress user → billing name → "Guest"), frontend state management in [`main.js:45`](assets/js/main.js:45) with `appState.orders.filters.customer` property, dynamic filter population via [`populateCustomerFilter()`](assets/js/main.js:1537-1561) that extracts unique customers from loaded orders excluding guests, event handler wired at [`main.js:634`](assets/js/main.js:634) triggers [`fetchOrders()`](assets/js/main.js:1524) with customer_filter parameter, filter persists selection across data refreshes when customer still exists in new dataset, updated version from v1.8.58 to v1.8.59 in [`index.php`](index.php:25)
- v1.8.58: Restored fee and discount columns to held carts table - corrected v1.8.57 change that inadvertently removed fee and discount columns, changed "Price" column label to "Total" for clarity, widened actions column from w-32 to w-40 for better spacing between Total and Actions, updated grid layout to `grid-cols-[auto,auto,1fr,auto,auto,auto,auto]` (7 columns: Date Held, Items, Customer, Fee, Discount, Total, Actions), fee displays as percentage or dollar amount in green color (`text-green-400`), discount displays as percentage or dollar amount in amber color (`text-amber-400`), maintained relative date formatting from v1.8.57, proper calculation shows subtotal + fee - discount = total in [`renderHeldCarts()`](assets/js/main.js:3382), updated version from v1.8.57 to v1.8.58 in [`index.php`](index.php:25)
- v1.8.57: Fixed held carts table layout and date formatting - implemented relative date display showing "Today @ HH:MM AM/PM", "Yesterday @ HH:MM AM/PM", or full date/time for older carts using new [`formatRelativeDate()`](assets/js/main.js:3346) helper function in [`renderHeldCarts()`](assets/js/main.js:3382), redesigned grid layout from `grid-cols-12` to `grid-cols-[auto,1fr,auto,auto,auto]` with fixed column widths (date: w-44 for "Yesterday @ 12:30 PM", items: w-16, price: w-24, actions: w-32) and flexible customer column (1fr) with truncation, eliminated jumbled column alignment issues, updated version from v1.8.56 to v1.8.57 in [`index.php`](index.php:25)
- v1.8.56: Fixed customer display not clearing after holding cart - modified [`clearCart()`](assets/js/main.js:1372) to set `appState.cart.customer = null` when fullReset parameter is true, ensures customer display is properly removed from UI after cart is held via [`renderCart()`](assets/js/main.js:1250) which calls [`renderCustomerDisplay()`](assets/js/main.js:1386), updated version from v1.8.55 to v1.8.56 in [`index.php`](index.php:25), resolves visual bug where customer box remained at top of cart even though customer data was cleared from state
- v1.8.55: Fixed held cart customer functionality - resolved three critical issues with customer data handling in held carts: 1) Modified [`holdCurrentCart()`](assets/js/main.js:3327) to save customer data (id, name, email) to held cart object before clearing cart, 2) Updated [`renderHeldCarts()`](assets/js/main.js:3344) to display customer name in held carts table with new customer column showing truncated name with tooltip or "-" if no customer attached, changed grid layout from 2-column items to 1-column items + 2-column customer display, 3) Fixed [`restoreHeldCart()`](assets/js/main.js:3466) to restore customer property from held cart data to `appState.cart.customer`, updated version from v1.8.54 to v1.8.55 in [`index.php`](index.php:25), customer now properly persists through hold/restore cycle
- v1.8.54: Implemented customer attachment functionality for POS orders - created customer search API endpoint [`api/customers.php`](api/customers.php:1) with WordPress user search supporting partial matching on name/email with 2+ character minimum, developed touch-friendly on-screen keyboard component [`assets/js/modules/keyboard.js`](assets/js/modules/keyboard.js:1) with letter layout optimized for quick search, added customer property to cart state in [`assets/js/modules/state.js`](assets/js/modules/state.js:32), implemented customer attachment UI with "Attach Customer" button and search modal in [`index.php`](index.php:377-1062), customer display shows at top of cart with name/email and remove option, integrated customer persistence with held carts in [`assets/js/modules/cart.js`](assets/js/modules/cart.js:120-520), customer data saved/restored with held cart state, event handlers and global functions in [`assets/js/main.js`](assets/js/main.js:1-4540), CSRF nonce protection with `jpos_customer_search_nonce`
- v1.8.53: Improved POS cart UI layout - moved Clear Cart button from bottom of button section (below Hold Cart/Checkout buttons) to directly below cart items area in [`index.php`](index.php:377), button now appears immediately after cart items list and before cart totals section, provides better visual hierarchy and more intuitive placement for clearing cart items
- v1.8.52: Removed product creation and image upload functionality from product editor - removed `create_product` action handler from [`api/product-edit-simple.php`](api/product-edit-simple.php:287-363), removed all image upload functions from [`assets/js/main.js`](assets/js/main.js:1700-2310) including `clearProductImages()`, `initializeImageUpload()`, `validateImageFile()`, featured/gallery upload handlers, removed Create Product button from [`index.php`](index.php:629-632), replaced image upload UI with disabled message in [`index.php`](index.php:786-858), updated version to 1.8.52 in [`index.php`](index.php:23), product editor now operates in edit-only mode requiring existing product IDs, all image management must be done through WooCommerce
- v1.8.51: Fixed product image upload file picker - changed file inputs in [`index.php`](index.php:813,850) from Tailwind's `hidden` class (display:none) to inline styles with `opacity:0` and `position:absolute`, resolves browser security restriction that blocks programmatic `.click()` calls on hidden elements, file picker now opens correctly when clicking upload areas for both featured and gallery images
- v1.8.50: Completely refactored image upload system with bulletproof implementation - replaced complex event delegation and cloning with simple direct onclick handlers in [`setupFeaturedImageUpload()`](assets/js/main.js:1887-1925) and [`setupGalleryImageUpload()`](assets/js/main.js:2122-2160), simplified [`clearProductImages()`](assets/js/main.js:1702-1720) to never manipulate innerHTML, guaranteed file picker functionality with most basic JavaScript approach (direct property assignment instead of addEventListener)
- v1.8.49: Fixed product image upload file picker - modified [`clearProductImages()`](assets/js/main.js:1702-1733) to include missing file input elements (`<input type="file" id="featured-image-input">` and `<input type="file" id="gallery-images-input" multiple>`) in dropzone innerHTML when resetting, resolves issue where file picker didn't open when clicking image upload areas because inputs were missing from DOM when [`initializeImageUpload()`](assets/js/main.js:1839) tried to attach event listeners
- v1.8.46: Fixed product creation API 400 error - modified [`product-edit-simple.php`](api/product-edit-simple.php:12-26) to check JSON POST body for action parameter when not found in URL query string, resolves "Action parameter required" error that prevented product creation from working, now properly detects action from both GET params and POST JSON body
- v1.8.45: Restored product creation functionality with two-step process - added "Create Product" button back to products page header at [`index.php:630`](index.php:630), restored `create_product` action handler in [`product-edit-simple.php:279-354`](api/product-edit-simple.php:279-354) with validation for required fields (name, regular_price), SKU uniqueness check, and WooCommerce product object creation, updated [`openProductEditor()`](assets/js/main.js:1758) to accept optional productId parameter defaulting to null for create mode, modified to show "Create Product" title and disable image uploads until product is saved, updated [`saveProductEditor()`](assets/js/main.js:3260) to detect mode from button attribute and handle both create and update operations, after successful creation switches to edit mode with returned product_id and enables image upload system via [`initializeImageUpload()`](assets/js/main.js:1819), wired create button event listener at [`main.js:638`](assets/js/main.js:638), incremented cache-busting version from v1.8.44 to v1.8.45 in [`index.php:23`](index.php:23) - simple two-step workflow: 1) create product with all text fields, 2) upload images after product exists
- v1.8.41: Removed product creation functionality - removed "Create Product" button from products page header at [`index.php:629-635`](index.php:629-635), removed entire `create_product` action handler from [`product-edit-simple.php:299-446`](api/product-edit-simple.php:299-446) including product validation and WooCommerce product object creation logic, updated [`openProductEditor()`](assets/js/main.js:1758) to require productId parameter and return early if null, updated [`saveProductEditor()`](assets/js/main.js:3260) to only handle update operations, updated [`initializeImageUpload()`](assets/js/main.js:1819) to only support edit mode with existing product data, removed temporary image upload system including [`tempProductImages`](assets/js/main.js:1699) storage object and all related functions - product editor now operates in edit-only mode for existing products
- v1.8.40: Fixed product gallery image upload for creation - corrected FormData construction in [`uploadGalleryImages()`](assets/js/main.js:2180) from `images[${index}]` to `images[]` to match PHP `$_FILES` array structure expected by [`product-images.php`](api/product-images.php:220), resolves issue where gallery images failed to upload when creating new products due to improper array notation that PHP couldn't recognize as proper array in `$_FILES['images']`
- v1.8.39: Fixed JavaScript scope error in product editor image upload - moved [`setupFeaturedImageTempUpload()`](assets/js/main.js:2581) and [`setupGalleryImageTempUpload()`](assets/js/main.js:2677) functions outside of [`initializeImageUpload()`](assets/js/main.js:1774) scope to same level as [`setupTemporaryImageUpload()`](assets/js/main.js:2617), resolved "setupFeaturedImageTempUpload is not defined" ReferenceError that occurred when [`setupTemporaryImageUpload()`](assets/js/main.js:2617) tried to call nested functions during product creation mode
- v1.8.38: Fixed upload button click handlers for pre-save image uploads - modified [`setupFeaturedImageTempUpload()`](assets/js/main.js:2280) and [`setupGalleryImageTempUpload()`](assets/js/main.js:2360) to replace addEventListener with direct event assignments (onclick, onchange, ondrop, ondragover, ondragleave), added element cloning to remove any conflicting event listeners, explicitly set pointerEvents='auto', opacity='1', and cursor='pointer' properties, added error logging for debugging, resolved issue where upload buttons didn't respond to clicks in product creation mode
- v1.8.37: Implemented pre-save image uploads for product creation - added [`tempProductImages`](assets/js/main.js:1699) global storage object, modified [`initializeImageUpload()`](assets/js/main.js:1774) to enable uploads in create mode via [`setupTemporaryImageUpload()`](assets/js/main.js:2268), implemented temporary storage handlers [`setupFeaturedImageTempUpload()`](assets/js/main.js:2280) and [`setupGalleryImageTempUpload()`](assets/js/main.js:2360) with drag-and-drop support, added preview display functions [`displayTempFeaturedImage()`](assets/js/main.js:2447) and [`displayTempGalleryImage()`](assets/js/main.js:2480), implemented cleanup functions [`removeTempFeaturedImage()`](assets/js/main.js:2507), [`removeTempGalleryImage()`](assets/js/main.js:2529), and [`clearTemporaryImages()`](assets/js/main.js:2549) with proper URL.revokeObjectURL() memory management, added [`uploadTemporaryImages()`](assets/js/main.js:2569) to upload stored images after product creation, modified [`saveProductEditor()`](assets/js/main.js:3645) to handle temp image upload and cleanup in create mode, supports File objects in browser memory, 5MB file size limit, 10-image gallery limit, validates PNG/JPG/JPEG/WebP/GIF formats
- v1.8.36: Enhanced barcode uniqueness in [`generate_unique_barcode()`](api/barcode.php:118) - removed JPOS prefix, changed from JPOS-YYMMDD-####-XX to YYYYMMDDHHMMSS-RAND format (e.g., 20251004230845-A3F7) using full timestamp with 4-char random alphanumeric suffix for maximum uniqueness
- v1.8.35: Fixed barcode API 404 error in [`handleBarcodeGeneration()`](assets/js/main.js:2779) - corrected endpoint URL from `/jpos/api/barcode.php` to `api/barcode.php` to match relative path pattern used by other product editor API calls (like [`product-edit-simple.php`](api/product-edit-simple.php:1)), resolves "Failed to load resource: the server responded with a status of 404" error during barcode generation
- v1.8.34: Implemented product barcode generation system - added "Generate" button in product editor at [`index.php:767-775`](index.php:767-775), new [`/api/barcode.php`](api/barcode.php:1) endpoint generates unique barcodes in JPOS-YYMMDD-####-XX format with CRC16 checksum validation and daily counter reset, frontend handler at [`handleBarcodeGeneration()`](assets/js/main.js:2765-2828) with loading states and error handling, includes CSRF nonce protection matching other API endpoints, uniqueness verified via wp_postmeta queries with retry logic for race conditions
- v1.8.30: Fixed print report functionality in [`handlePrintReports()`](assets/js/main.js:3856) and [`printReport()`](assets/js/main.js:3886) - replaced problematic `window.print()` with reliable `window.open()` approach matching receipt printing, eliminated complex CSS visibility system causing blank pages, added comprehensive print-optimized CSS styles, ensures all report content displays without truncation and handles page breaks correctly
- v1.8.28: Incremented version parameter in [`index.php`](index.php:23) from v1.8.27 to v1.8.28 - force browser cache invalidation to resolve issue where users loading cached v1.8.26 encountered "cart is not defined" ReferenceError during checkout, current code correctly uses `appState.cart.items` throughout but browsers were serving outdated cached versions
- v1.8.27: Fixed checkout ReferenceError in [`getCartTotal()`](assets/js/main.js:3591) and [`openSplitPaymentModal()`](assets/js/main.js:3557) - updated undefined `cart` variable references to `appState.cart.items` to match centralized state management architecture, resolves "cart is not defined" error that prevented split payment modal from opening and cart totals from calculating correctly during checkout
- v1.8.26: Enhanced print report period display in [`generatePrintReport()`](assets/js/main.js:3897) - replaced relative period terms ("today", "yesterday", "this week") with actual dates from API data, shows single date for single-day periods (e.g., "1/2/2025") or date range for multi-day periods (e.g., "1/1/2025 - 1/7/2025"), provides more precise and professional period information
- v1.8.25: Fixed print report terminology in [`generatePrintReport()`](assets/js/main.js:3940) - changed items table header from "Total" to "Subtotals" to distinguish individual item amounts from the main order total displayed at bottom, improves clarity and follows standard receipt formatting conventions
- v1.8.24: Improved print report layout in [`generatePrintReport()`](assets/js/main.js:3971) - moved order total from top-right header to bottom of each order card with "Total:" label and amount aligned horizontally, added border separator above total section, removed total from header area to focus on order info (number, date, customer, payment method, status)
- v1.8.23: Improved print report item display format in [`generatePrintReport()`](assets/js/main.js:3954) - changed from separate quantity column to "Qty x Item Name" format (e.g., "2x Product Name"), simplified table to 2 columns (Item, Total), improved readability and space efficiency for receipt-style printing
- v1.8.22: Fixed print report items data structure handling in [`generatePrintReport()`](assets/js/main.js:3953) - identified that order.items is an object with numeric keys (e.g., `{9716: {...}, 9715: {...}}`) not an array, implemented `Object.values(order.items)` to convert to array for proper iteration, removed debug logging, now correctly displays all order items in print report
- v1.8.21: Fixed print report items iteration error in [`generatePrintReport()`](assets/js/main.js:3949) - added proper array checking (`Array.isArray(order.items)`) and fallback handling to prevent "order.items.forEach is not a function" error, includes debugging console logs to identify data structure issues, gracefully handles cases where items data is missing or malformed
- v1.8.20: Enhanced print report functionality in [`generatePrintReport()`](assets/js/main.js:3918) - replaced generic table format with detailed receipt-style layout showing individual order items (name, quantity, total), removed irrelevant columns (source, status), improved visual presentation with bordered order cards, payment method and customer information display, and proper item-by-item breakdown for each order
- v1.8.19: Fixed print report error in [`generatePrintReport()`](assets/js/main.js:3893) - added proper null checking for `appState.settings.receipt.store_name` to prevent "Cannot read properties of undefined" error, now uses optional chaining operator (`?.`) and fallback to 'Store' when settings are not loaded
- v1.8.18: Implemented comprehensive reporting system - added [`/api/reports.php`](api/reports.php:1) endpoint with intelligent time granularity (hourly for intraday, daily for weekly/monthly, monthly for 2+ months, yearly for multi-year periods), dynamic period selection (today, yesterday, this week, last week, this month, this year, custom range), Chart.js visualization with dual-axis revenue and order count trends, summary statistics display, comprehensive order details list, and receipt-style print preview modal with formatted reports suitable for physical printing or PDF export. Updated routing system to include [`reports-page`](assets/js/modules/routing.js:13) view and [`fetchReportsData()`](assets/js/main.js:3624) global function, integrated with existing navigation patterns and design system
