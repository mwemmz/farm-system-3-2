# Full Farm Management System (FFMS)

## Contributing Guidelines

To contribute to this project, please adhere to the following standards:

1. **Architecture:** Use the `/api/{module}/{action}.php` folder structure.
2. **Naming:** Use `snake_case` for database columns/tables and `camelCase` for PHP variables/functions.
3. **API Responses:** All endpoints must return JSON:
   - Success: `{ "success": true, "data": {}, "error": null }`
   - Failure: `{ "success": false, "data": null, "error": "message" }`
4. **Database:** Use the shared `db.php` PDO wrapper with prepared statements. No raw SQL concatenation.
5. **Authentication:** All protected endpoints must call `verifyJWT()` from `auth.php`.
6. **Migrations:** Use numbered SQL migration files (e.g., `002_inventory.sql`).
7. **Security:** Never leak raw PHP errors. Use `logAudit()` for all write actions.
8. **Configuration:** No hardcoded secrets. Use environment variables.

Please follow the phase order outlined in `ffms_agent_prompt_dev2-3-4.md` for any new development.
