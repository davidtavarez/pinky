## pinky v2

![pinky v2](https://github.com/davidtavarez/pinky/raw/master/screenshots/pinkyV2.png "pinky v2")

### The PHP mini RAT

Upload a webshell is the next step after exploiting a web vulnerability, but the services like Cloudflare and the new generation of firewalls does a really good job preventing an attacker runs commands in the target via HTTP.

**pinky** is a PoC.

### How pinky is different?

First, **pinky** checks which function is enabled to run commands and **every communication is encrypted**, so even the Firewall is enabled to check the traffic it won't be able to know whether the activity is malicious or not. Also, pinky is able to communicate through a SOCKS5 proxy.

### How to use it.

Upload the agent and then create a json file with the settings.

```
{
  "key":"[KEY]",
  "iv": "[IV]",
  "url":"[URL]",
  "login":{
    "username":"root",
    "password":"toor"
  },
  "proxy":{
    "ip":"127.0.0.1",
    "port":9150,
    "type":"SOCKS5"
  }
}
```

Now, open your terminal and pass the json file as a parameter.

```
$ php pinky.php config.json
```
