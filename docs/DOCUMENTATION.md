# EasyAPP Framework Documentation

**Version:** 2.0  
**Author:** YoYo  
**Copyright:** 2022-2025, script-php.ro  
**License:** GPL v3

---

## Table of Contents

### Getting Started
- [Installation and Setup](01-getting-started.md)
- [Configuration](02-configuration.md)
- [Directory Structure](03-directory-structure.md)

### Core Concepts
- [Architecture Overview](04-architecture.md)
- [Request Lifecycle](05-request-lifecycle.md)
- [Dependency Injection](06-dependency-injection.md)

### MVC Components
- [Controllers](07-controllers.md)
- [Models (Traditional)](08-models-traditional.md)
- [Models (ORM)](09-models-orm.md)
- [Views](10-views.md)
- [Model Loading Patterns](14-model-loading.md)

### Additional Components
- [Services](11-services.md)
- [Libraries](12-libraries.md)
- [Language Files](13-language.md)
- [Helpers](14-helpers.md)

### Routing
- [Routing System](15-routing.md)
- [URL Generation](16-url-generation.md)

### Database
- [Database Usage](17-database.md)
- [Query Builder](18-query-builder.md)
- [ORM Relationships](19-orm-relationships.md)
- [Migrations](20-migrations.md)

### Advanced Topics
- [Events System](21-events.md)
- [Caching](22-caching.md)
- [Validation](23-validation.md)
- [Security](24-security.md)
- [Logging](25-logging.md)

### Development Tools
- [CLI Commands](26-cli-commands.md)
- [Testing](27-testing.md)
- [Debugging](28-debugging.md)

### API Reference
- [Core Classes](29-core-classes.md)
- [Helper Functions](30-helper-functions.md)

---

## Quick Links

- **GitHub Repository:** https://github.com/script-php/EasyAPP
- **Official Website:** https://script-php.ro
- **Issue Tracker:** https://github.com/script-php/EasyAPP/issues

---

## Documentation Conventions

Throughout this documentation, you will encounter several conventions:

**Code Blocks:**
```php
// PHP code examples are shown in code blocks
$example = "value";
```

**File Paths:**
- Absolute paths are shown from project root
- Example: `app/controller/home.php`

**Placeholders:**
- Items in angle brackets should be replaced
- Example: `<YourClassName>` becomes `UserController`

**Notes:**
```
Note: Important information that requires attention
```

**Warnings:**
```
Warning: Critical information about potential issues
```

---

## Prerequisites

Before using EasyAPP Framework, ensure your environment meets these requirements:

- **PHP Version:** 7.4 or higher
- **Extensions:**
  - PDO (for database functionality)
  - JSON
  - MBString (recommended)
- **Web Server:**
  - Apache with mod_rewrite, or
  - Nginx with proper configuration
- **Composer:** Optional, for dependency management

---

## Framework Philosophy

EasyAPP is designed with the following principles:

1. **Simplicity First:** Easy to learn and use
2. **Performance:** Lightweight and fast execution
3. **Flexibility:** Adapt to various project needs
4. **Modern Features:** Contemporary PHP practices
5. **Minimal Dependencies:** Self-contained framework

---

## Support

If you need assistance:

1. Check the documentation sections listed above
2. Review example code in the repository
3. Visit the GitHub Issues page
4. Contact the development team

---

## License

EasyAPP Framework is open-source software licensed under the GPL v3 License.

```
Copyright (c) 2022-2025, script-php.ro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License.
```

---

**Next:** [Installation and Setup](01-getting-started.md)
