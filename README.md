# Cloud WAF Security Demo

This project is a PHP/MySQL e-commerce web application used as a practical demo for the topic:

> Cloud computing and security issues in cloud computing.

The demo focuses on the security part of the topic: protecting a public web application deployed on an Internet/cloud hosting environment with a Web Application Firewall (WAF).

## Demo Architecture

```text
User / Attacker
      |
   Internet
      |
WAF inspection layer
      |
PHP web application
      |
MySQL database / hosting database
```

## Main Security Features

- SQL Injection detection and blocking
- XSS detection and blocking
- Command Injection detection and blocking
- Path Traversal detection and blocking
- DDoS/rate-limit simulation
- Attack logs and blocked IP management
- Admin dashboard for WAF configuration and monitoring

The WAF code is implemented in `waf/LizWAF.php` and is integrated through `includes/db.php`.

## Relation To Cloud Computing Security

This demo does not try to implement a full cloud platform. It demonstrates one concrete security control used in cloud environments: a WAF layer in front of an application that is reachable from the Internet.

This maps to real cloud services such as:

- AWS WAF
- Azure Web Application Firewall
- Google Cloud Armor

## Demo Flow For Review

1. Open the website and show normal user access.
2. Open the WAF dashboard as admin.
3. Send SQL Injection, XSS, Path Traversal, or high-rate requests.
4. Show that malicious requests are blocked.
5. Show attack logs/statistics in the dashboard.

## Configuration

Do not commit real database credentials. Configure the application with environment variables:

```text
DB_HOST
DB_USER
DB_PASS
DB_NAME
SITE_URL
```

See `.env.example` for sample values.

The real database dump is intentionally not committed because it may contain account data and deployment-specific values. For source-code review, the important parts are the WAF implementation, integration point, dashboard, and demo flow.
