import asyncio, json, sys
import websockets

async def main():
    async with websockets.connect("ws://localhost:8765") as ws:
        await ws.send(json.dumps({
            "otp": sys.argv[1],
            "signature": sys.argv[3] if len(sys.argv) > 3 else "signature",
            "user_id": sys.argv[2] if len(sys.argv) > 2 else "user"
            }))
        print(await ws.recv())

if __name__ == "__main__":
    asyncio.run(main())