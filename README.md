## pinky
### The (reverse) PHP mini RAT

A reverse shell works by the remote computer sending its shell to a specific user, rather than binding it to a port, which would be unreachable in many circumstances. This allows run commands over the remote server.

**pinky** is a minimal php implementantion of a reverse remote administration tool.

### Why?

Truth is there are a lot of implementations out there in other programming languages, but not a good one written in PHP. Most of the code makes use of the **shell_exec** function to execute commands and this is very limited. **pynky** use **proc_open** to pass  input and catch the output to send it through a socket connection.

### How to use it.

All you need is to execute the file: pynky.php using the web browser or within the CLI.
