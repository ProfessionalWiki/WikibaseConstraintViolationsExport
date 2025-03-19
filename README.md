# Wikibase Quality Constraints Export

Enable extension in LocalSettings:
```php
wfLoadExtension( 'WikibaseQualityConstraintsExport' );
```

To export constraint violations in JSON format, run:
```bash
php maintenance/run.php ./extensions/WikibaseQualityConstraintsExport/maintenance/ExportConstraintViolations.php
```
