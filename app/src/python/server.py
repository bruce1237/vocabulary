import http.server
import socketserver
import subprocess
import os

PORT = 8080

class ScriptHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/cgi-bin/index.py":
            # Define the script to be executed
            script_path = os.path.join(os.getcwd(), 'index.py')
            if os.path.exists(script_path):
                # Execute the Python script and capture the output
                result = subprocess.run(['python3', script_path], capture_output=True, text=True)
                # Send response status code
                self.send_response(200)
                # Send headers
                self.send_header("Content-type", "text/html")
                self.end_headers()
                # Send the output
                self.wfile.write(result.stdout.encode())
            else:
                # Send a 404 response if the script doesn't exist
                self.send_response(404)
                self.end_headers()
                self.wfile.write(b"Script not found.")
        else:
            # For other requests, serve the file as static content
            super().do_GET()

# Set up the server
with socketserver.TCPServer(("", PORT), ScriptHandler) as httpd:
    print(f"Serving at port {PORT}")
    httpd.serve_forever()