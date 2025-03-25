# Wikibase Constraint Violations Export

## Installation

Place the extension in the `extensions/` directory.

Then enable the extension in LocalSettings:
```php
wfLoadExtension( 'WikibaseConstraintViolationsExport' );
```

## Usage

To export constraint violations in JSON format, run:
```bash
php maintenance/run.php ./extensions/WikibaseConstraintViolationsExport/maintenance/ExportConstraintViolations.php
```

## Example Output

```json
{
  "Q50": [
    {
      "status": "warning",
      "propertyId": "P23",
      "messageKey": "wbqc-violation-message-range-time-closed",
      "message": "The value for 'date of birth' ('1337') should be between '1900' and '2025'.",
      "constraintId": "P23$3a6b0bea-46dd-2de6-d9cc-5836b056d095",
      "constraintType": "Q17",
      "value": "1337"
    }
  ],
  "Q53": [
    {
      "status": "warning",
      "propertyId": "P27",
      "messageKey": "wbqc-violation-message-units",
      "message": "The value for 'weight' should have one of the following units: ('kg', 'lb', 'g')",
      "constraintId": "P27$94f07a4b-4ecb-cb2c-1b33-e6b1097b39c0",
      "constraintType": "Q25",
      "value": "1 stone"
    },
    {
      "status": "warning",
      "propertyId": "P25",
      "messageKey": "wbqc-violation-message-range-quantity-rightopen",
      "message": "The value for 'pages' ('−1') should be no less than '1'.",
      "constraintId": "P25$1f5f73aa-44df-3459-ec15-92437da0a789",
      "constraintType": "Q17",
      "value": "−1"
    }
  ],
  "Q54": [
    {
      "status": "warning",
      "propertyId": "P26",
      "messageKey": "wbqc-violation-message-valueType-instanceOrSubclass",
      "message": "Values of 'author' statements should be instances or subclasses of 'human' (or of a subclass of it), but 'CSS for Dummies' currently isn't.",
      "constraintId": "P26$63c3096f-4878-61d7-2bdb-5e5805de2fa3",
      "constraintType": "Q9",
      "value": "CSS for Dummies"
    }
  ],
  "Q55": [
    {
      "status": "warning",
      "propertyId": "P23",
      "messageKey": "wbqc-violation-message-single-value",
      "message": "This property should only contain a single value.",
      "constraintId": "P23$f9de5045-4538-34d9-3cb9-3777f717f2cf",
      "constraintType": "Q6",
      "value": "2001"
    },
    {
      "status": "warning",
      "propertyId": "P23",
      "messageKey": "wbqc-violation-message-single-value",
      "message": "This property should only contain a single value.",
      "constraintId": "P23$f9de5045-4538-34d9-3cb9-3777f717f2cf",
      "constraintType": "Q6",
      "value": "2003"
    },
    {
      "status": "warning",
      "propertyId": "P23",
      "messageKey": "wbqc-violation-message-single-value",
      "message": "This property should only contain a single value.",
      "constraintId": "P23$f9de5045-4538-34d9-3cb9-3777f717f2cf",
      "constraintType": "Q6",
      "value": "2005"
    }
  ]
}
```
