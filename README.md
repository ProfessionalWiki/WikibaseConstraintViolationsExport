# Wikibase Constraint Violations Export

Enable extension in LocalSettings:
```php
wfLoadExtension( 'WikibaseConstraintViolationsExport' );
```

To export constraint violations in JSON format, run:
```bash
php maintenance/run.php ./extensions/WikibaseConstraintViolationsExport/maintenance/ExportConstraintViolations.php
```
