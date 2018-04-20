# Consent Log

This is a re-work of a plugin that I've made to log consents in WordPress. This repo serves the purpose of expanding and refactoring this until we see if we can/have to add it to core as an extra tool for #gdpr-compliance.

PRs / suggestions etc are welcome either here or on https://core.trac.wordpress.org/ticket/43797

## How to use:


### Initialize the Consent_Log
`$cl_consent = new Consent_Log();`

### Add a new Consent
`$consent = $cl_consent->cl_add_consent( 'test@test.gr', 'form_1', 1 );`

### Remove a Consent
`$consent = $cl_consent->cl_remove_consent( 'test@test.gr', 'form_1' );`

### Update a Consent
`$consent = $cl_consent->cl_update_consent( 'test@test.gr', 'form_1', 0 )`

### Check if Consent Exists
`$consent = $cl_consent->cl_consent_exists( 'test@test.gr', 'form_1' );`

### Check if the consent is Accepted
`$consent = $cl_consent->cl_has_consent( 'test@test.gr', 'form_1' );`

#### Contributors

[xkon](https://github.com/mrxkon), [aristah](https://github.com/aristath)