## pinky v2

![pinky v2](https://github.com/davidtavarez/pinky/raw/master/screenshots/pinkyV2_banner.png "pinky v2")

### The PHP mini RAT

Uploading a webshell is almost always the next step after exploiting a web vulnerability, but services like Cloudflare and the new generation of firewalls does a really good job preventing attackers to run commands in the target via HTTP or HTTPS. Web filtering for content and whitelist applications policy can be easily exploited with a minimum effort and **pinky is a PoC** of that.

### How pinky is different?

First, **pinky** try to find which function is enabled to run system commands; after finding which php function is the best, **all communication is encrypted**, so even the Firewall is enabled to check the traffic it won't be able to know whether the activity is malicious or not. Also, **pinky is able to communicate through any kind of proxy**. In addition of this, we need to send a Basic Authentication (completely, I know) to avoid others to communicate with the agent.

### How to use it.

First, exploit the vulnerability founded on your target.

Now, we're ready to generate our agent using the **built-in generator** like this:

![pinky v2](https://github.com/davidtavarez/pinky/raw/master/screenshots/pinkyV2_generator.png "pinky v2 agent generator")

I'm using **Obfuscator-Class** by **Pierre-Henry Soria** to obfuscate the agent and the result is pretty good.

![pinky v2](https://github.com/davidtavarez/pinky/raw/master/screenshots/pinkyV2_virustotal.png "virus total")

After the agent is generated we need to upload it into the target machine and paste the URL into the json file created previously. If we want (and we must), use a SOCKS5 proxy, we need to add the settings:

```
{
  "key":"[KEY]",
  "iv": "[IV]",
  "url":"[URL]",
  "login":{
    "username":"[LOGIN]",
    "password":"[PASSWORD]"
  },
  "proxy":{
    "ip":"127.0.0.1",
    "port":9150,
    "type":"SOCKS5"
  }
}
```

The last step is open your terminal and then pass the json file as a parameter.

```
$ php pinky.php config.json
```

![pinky v2](https://github.com/davidtavarez/pinky/raw/master/screenshots/pinkyV2_connecting.png "pinky v2")

![pinky v2](https://github.com/davidtavarez/pinky/raw/master/screenshots/pinkyV2.png "pinky v2")
