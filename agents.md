
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
**Last Updated**: October 6, 2025
**Version**: 1.8.70
**All Phases Completed**: Security, Architecture, Performance, Quality & Monitoring
**Latest Update**: WP POS v1.8.70 - Unified settings save logic for consistent behavior across all settings - simplified [`api/settings.php`](api/settings.php:53-82) to treat all settings equally by comparing complete old vs new settings objects, removed separate keyboard settings logic, now ALL settings (receipt fields and keyboard booleans) use same unified change detection mechanism via array comparison, displays "Settings saved successfully" only when actual changes detected regardless of field type, updated cache-busting version from v1.8.69 to v1.8.70 in [`index.php:25`](index.php:25)

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

## Version History

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
