# Demo Script

Use this script when explaining the project as a cloud security demo.

## 1. Normal Request

Open the home page or product page and show that normal traffic passes through the application.

Expected result:

- The PHP application responds normally.
- WAF does not block safe input.

## 2. SQL Injection Test

Try a search keyword or URL parameter containing:

```text
+' OR '1'='1
```

Expected result:

- WAF detects SQL Injection patterns.
- Request is blocked or logged.
- Dashboard shows the attack type in recent logs/statistics.

## 3. XSS Test

Try an input containing:

```html
<script>alert(1)</script>
```

Expected result:

- WAF detects script-based payload.
- Request is blocked or logged.
- Dashboard records an XSS event.

## 4. Path Traversal Test

Try an input containing:

```text
../../etc/passwd
```

Expected result:

- WAF detects path traversal.
- Request is blocked or logged.

## 5. DDoS / Rate Limit Simulation

Use the DDoS demo pages if available:

```text
ddos_proxy.php
ddos_target.php
```

Expected result:

- WAF records high-rate traffic.
- Rate-limit or block behavior appears in dashboard statistics.

## 6. Explain The Cloud Security Link

Use this sentence:

> The website is the cloud-hosted application. The WAF is the security layer in front of the application, similar to AWS WAF, Azure WAF, or Google Cloud Armor. The demo shows how cloud-facing applications can be monitored and protected from common web attacks.
