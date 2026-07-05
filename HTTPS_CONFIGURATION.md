# HTTPS Configuration Guide
## Secure SSL/TLS Setup for Push Notifications

**Status**: ✅ **DOCUMENTED**
**Purpose**: Web Push API requires HTTPS (except localhost)

---

## Why HTTPS is Required

Web Push API and Service Workers require HTTPS for security reasons:
- **Service Workers**: Only work on secure contexts (HTTPS or localhost)
- **Push Notifications**: Require encrypted connections for security
- **Browser Security**: Modern browsers block push on HTTP for security

**Exceptions**:
- `http://localhost` - Works for development
- `http://127.0.0.1` - Works for development
- All other domains require HTTPS

---

## SSL Certificate Options

### Option 1: Let's Encrypt (Free, Recommended)

**Best for**: Production servers, personal domains

**Requirements**:
- Domain name with DNS pointing to your server
- Shell access to server
- Port 80/443 open

**Installation** (Ubuntu/Debian with Apache):
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtain certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
```

**Installation** (Ubuntu/Debian with Nginx):
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
```

**Renewal**:
```bash
# Test renewal
sudo certbot renew --dry-run

# Manual renewal
sudo certbot renew
```

### Option 2: Cloudflare SSL (Free)

**Best for**: Cloudflare CDN users

**Steps**:
1. Sign up at Cloudflare
2. Add your domain
3. Change nameservers to Cloudflare
4. Enable "Flexible SSL" or "Full SSL"
5. SSL is handled by Cloudflare edge servers

**SSL Modes**:
- **Flexible**: Cloudflare → User (SSL), Cloudflare → Server (HTTP)
- **Full**: Cloudflare → User (SSL), Cloudflare → Server (SSL)
- **Full (Strict)**: Validates server certificate

### Option 3: Self-Signed Certificate (Development Only)

**Best for**: Local development testing

**Generate Self-Signed Certificate**:
```bash
# Generate private key
openssl genrsa -out server.key 2048

# Generate CSR
openssl req -new -key server.key -out server.csr

# Generate self-signed certificate (valid for 365 days)
openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
```

**Configure Apache**:
```apache
<VirtualHost *:443>
    ServerName localhost
    DocumentRoot /var/www/html
    
    SSLEngine on
    SSLCertificateFile /path/to/server.crt
    SSLCertificateKeyFile /path/to/server.key
</VirtualHost>
```

**Configure Nginx**:
```nginx
server {
    listen 443 ssl;
    server_name localhost;
    
    ssl_certificate /path/to/server.crt;
    ssl_certificate_key /path/to/server.key;
    
    root /var/www/html;
}
```

**Browser Warning**: Self-signed certificates will show security warnings in browsers.

### Option 4: Commercial SSL Certificate

**Best for**: Enterprise, e-commerce, high-trust applications

**Providers**:
- DigiCert
- Comodo (Sectigo)
- GeoTrust
- GlobalSign

**Process**:
1. Purchase certificate from provider
2. Generate CSR on server
3. Submit CSR to provider
4. Download certificate files
5. Install on server

---

## Apache HTTPS Configuration

### Enable SSL Module
```bash
sudo a2enmod ssl
sudo a2enmod headers
sudo systemctl restart apache2
```

### Virtual Host Configuration
```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/abuu-nufaysah-university/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/yourdomain.com.crt
    SSLCertificateKeyFile /etc/ssl/private/yourdomain.com.key
    SSLCertificateChainFile /etc/ssl/certs/chain.pem
    
    # SSL Protocols (disable old, insecure protocols)
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    
    # SSL Cipher Suites
    SSLCipherSuite HIGH:!aNULL:!MD5:!3DES
    SSLHonorCipherOrder on
    
    # HSTS (HTTP Strict Transport Security)
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Directory configuration
    <Directory /var/www/abuu-nufaysah-university/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/abuu-nufaysah-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/abuu-nufaysah-ssl-access.log combined
</VirtualHost>

# HTTP to HTTPS Redirect
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

---

## Nginx HTTPS Configuration

### SSL Configuration
```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/abuu-nufaysah-university/public;
    index index.php;

    # SSL Certificate Configuration
    ssl_certificate /etc/ssl/certs/yourdomain.com.crt;
    ssl_certificate_key /etc/ssl/private/yourdomain.com.key;
    ssl_trusted_certificate /etc/ssl/certs/chain.pem;

    # SSL Protocols
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    # SSL Cipher Suites
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';

    # SSL Session Configuration
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;

    # OCSP Stapling
    ssl_stapling on;
    ssl_stapling_verify on;
    ssl_trusted_certificate /etc/ssl/certs/chain.pem;

    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Location configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }

    location ~ /config/ {
        deny all;
    }

    error_log /var/log/nginx/abuu-nufaysah-ssl-error.log;
    access_log /var/log/nginx/abuu-nufaysah-ssl-access.log;
}

# HTTP to HTTPS Redirect
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## .htaccess HTTPS Redirect

### Force HTTPS
Add to `public/.htaccess`:
```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# HSTS Header
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" env=HTTPS
</IfModule>
```

---

## Testing HTTPS Configuration

### SSL Certificate Test
Use online tools to test your SSL configuration:
- **SSL Labs**: https://www.ssllabs.com/ssltest/
- **SSL Checker**: https://www.sslshopper.com/ssl_checker.html
- **HTTP Security Headers**: https://securityheaders.com/

### Manual Testing
```bash
# Test HTTPS connection
curl -I https://yourdomain.com

# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Check certificate chain
openssl s_client -connect yourdomain.com:443 -showcerts
```

### Browser Testing
1. Open browser
2. Navigate to `https://yourdomain.com`
3. Check for padlock icon in address bar
4. Click padlock to view certificate details
5. Check for security warnings

---

## Common Issues & Solutions

### Mixed Content Warning
**Problem**: HTTP resources on HTTPS page

**Solution**:
- Update all resource URLs to HTTPS
- Use protocol-relative URLs: `//cdn.example.com/style.css`
- Add Content Security Policy header

### Certificate Not Trusted
**Problem**: Self-signed or expired certificate

**Solution**:
- Use trusted CA (Let's Encrypt, commercial)
- Renew expired certificates
- Install intermediate certificates

### HSTS Preload Issues
**Problem**: Site stuck on HTTPS after enabling HSTS preload

**Solution**:
- Remove from HSTS preload list
- Clear browser HSTS data
- Wait for max-age to expire

### Service Worker Not Registering
**Problem**: Service worker fails on HTTPS

**Solution**:
- Ensure HTTPS is properly configured
- Check service worker file is accessible
- Verify SSL certificate is valid
- Check browser console for errors

---

## Production Deployment Checklist

### SSL Configuration
- [ ] SSL certificate installed
- [ ] Private key secured (chmod 600)
- [ ] Intermediate certificates installed
- [ ] SSL protocols restricted to TLSv1.2+
- [ ] Strong cipher suites configured
- [ ] HSTS header enabled
- [ ] Security headers configured
- [ ] HTTP to HTTPS redirect enabled
- [ ] SSL certificate auto-renewal configured

### Application Configuration
- [ ] Update `APP_URL` in `.env` to HTTPS
- [ ] Update `SESSION_COOKIE_SECURE=true` in `.env`
- [ ] Update VAPID keys if domain changed
- [ ] Test push notifications on HTTPS
- [ ] Verify service worker registration
- [ ] Test all functionality over HTTPS

### Testing
- [ ] SSL Labs test (A+ rating)
- [ ] Security headers test
- [ ] Browser compatibility test
- [ ] Push notification test
- [ ] Service worker test
- [ ] Mixed content check

---

## Security Best Practices

### SSL Configuration
- Use TLSv1.2 or higher only
- Disable SSLv2, SSLv3, TLSv1, TLSv1.1
- Use strong cipher suites
- Enable HSTS with preload
- Use OCSP stapling
- Keep certificates renewed

### Headers
```apache
# Required Headers
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin

# Optional Headers
Content-Security-Policy: default-src 'self'
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

### Certificate Management
- Set up auto-renewal (Let's Encrypt)
- Monitor certificate expiration
- Keep private keys secure
- Use strong private keys (2048-bit minimum)
- Rotate keys periodically

---

## Cost Comparison

| Option | Cost | Best For | Difficulty |
|--------|------|----------|------------|
| Let's Encrypt | Free | Production servers | Easy |
| Cloudflare SSL | Free | CDN users | Easy |
| Self-Signed | Free | Development only | Easy |
| Commercial SSL | $50-$500/year | Enterprise | Medium |

---

## Troubleshooting

### Certificate Issues
```bash
# Check certificate expiration
openssl x509 -in /path/to/cert.crt -noout -dates

# Check certificate chain
openssl s_client -connect yourdomain.com:443 -showcerts

# Verify certificate
openssl verify /path/to/cert.crt
```

### Apache Issues
```bash
# Check Apache SSL module
sudo a2enmod ssl
sudo systemctl restart apache2

# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Test Apache configuration
sudo apache2ctl configtest
```

### Nginx Issues
```bash
# Check Nginx configuration
sudo nginx -t

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Restart Nginx
sudo systemctl restart nginx
```

---

## References

- **Let's Encrypt**: https://letsencrypt.org/
- **SSL Labs**: https://www.ssllabs.com/
- **Mozilla SSL Config Generator**: https://ssl-config.mozilla.org/
- **HSTS Preload**: https://hstspreload.org/
- **Cloudflare SSL**: https://www.cloudflare.com/ssl/

---

**Documentation Completed**: 2024
**Status**: ✅ Ready for HTTPS Configuration
