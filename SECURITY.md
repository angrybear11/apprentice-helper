# Using Apprentice Securely

This document describes how we protect your data in Apprentice as well as how to disclose any security issues you find and how we’ll notify you of any security updates.


## Network Security

All network-based communication (e.g. authenticating, running commands) is only between your device and your server. This connection is protected using Apple’s latest App Transport Security requirements. To meet those requirements your server must:

- Use HTTPS
- Have a valid certificate signed by a certificate authority
- Use TLS 1.2 or greater

For more details on how Apprentice protects your network requests see https://developer.apple.com/documentation/security/preventing_insecure_network_connections.


## Secure Authentication

When authenticating to your project, Apprentice currently only supports HTTP Signature authentication using ECDSA-SHA512. This provides a few benefits:

- Instead of a password, Apprentice creates a unique public/private key for each device you register with your server.
- Your private key is stored securely in the secure enclave on your device. This means that your key cannot be accessed, exported, or shared with other devices.
- Your biometrics (FaceID or TouchID) are used to authenticate you and authorize requests to your server. For your convenience, Apprentice doesn’t require your biometrics for subsequent commands you execute on the same project for up to 1 minute. Closing the app locks access to your project immediately.
- Apprentice doesn’t collect any password-based credentials from you. Our data retention policy is if we don’t need it, we don’t want it. For more details about our privacy policy see http://getapprentice.com/privacy.


## Disclosure Policy

Please report any suspected security vulnerabilities to security@voronoi.me. We will respond to you in a timely manner. If the issue is confirmed, we will release a patch as soon as possible.


## Security Update Policy

We will release an app update alongside any vulnerabilities we patch with this plugin. We suggest turning auto-updates on for your iOS device. You will be notified in the app when selecting a project if a project’s plugin is not using the latest required version. Until the package is updated, you won’t be able to connect to your project.
