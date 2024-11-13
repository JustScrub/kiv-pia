import asyncio, json, sys
import websockets

async def main():
    async with websockets.connect("ws://localhost:8765") as ws:
        await ws.send(json.dumps({
            "web-otp": sys.argv[1]
            }))
        while True:
            try:
                print("Server: " + await ws.recv())
            except websockets.exceptions.ConnectionClosed:
                print("Connection closed")
                break

if __name__ == "__main__":
    asyncio.run(main())