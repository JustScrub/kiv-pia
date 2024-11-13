import asyncio, json
import websockets

class otp_data:
    def __init__(self, otp):
        self.otp = otp
        self.api_resp = asyncio.get_event_loop().create_future()

CONNS = dict()

async def web_client(websocket, otp):
    
    if otp in CONNS:
        await websocket.send(json.dumps({"status": "OTP in use"}))
        return

    CONNS[otp] = otp_data(otp)
    await websocket.send(json.dumps({"status": "OTP recieved", "otp": otp}))

    try:
        (sig, user_id, otp2) = await asyncio.wait_for(CONNS[otp].api_resp, timeout=60)
    except asyncio.TimeoutError:
        await websocket.send(json.dumps({"status": "OTP timeout"}))
        del CONNS[otp]
        return

    await websocket.send(json.dumps(
        {
            "status": "OTP forward", 
            "signature": sig, 
            "user_id": user_id, 
            "otp": otp2
        }))
    del CONNS[otp]

async def handler(websocket):
    data = await websocket.recv()
    print(f"Recieved: {data}")
    data = json.loads(data)

    if "web-otp" in data:
        # Web client
        try:
            await web_client(websocket, data["web-otp"])
        except websockets.exceptions.ConnectionClosed:
            #print(f"Connection {data['web-otp']} closed")
            del CONNS[data["web-otp"]]
            pass
    elif all(x in data for x in ["signature", "user_id", "otp"]):
        # API client
        otp = data["otp"]
        retries = 5
        while retries > 0:
            if otp in CONNS:
                CONNS[otp].api_resp.set_result((data["signature"], data["user_id"], otp))
                await websocket.send("OK")
                break
            await asyncio.sleep(1)
            retries -= 1
        else:
            await websocket.send("OTP not found")

async def main():
    async with websockets.serve(handler, "", 8765):
        await asyncio.Future()

if __name__ == "__main__":
    asyncio.run(main())
