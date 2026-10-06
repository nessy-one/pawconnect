#!/usr/bin/env python3
"""
rfid_bridge.py

Reads scanned tag UIDs from an Arduino over USB serial and forwards each
one to the PawConnect rfid_scan.php endpoint. Runs continuously on the
PC that's plugged into the Arduino.

Install once:
    pip install pyserial requests

Run:
    python rfid_bridge.py
"""

import time
import serial
import requests

SERIAL_PORT = "COM7"  # e.g. "COM3" on Windows, "/dev/ttyUSB0" 
# kapag d nagwork com 3 lagay
BAUD_RATE = 9600
SERVER_URL = "http://localhost/pawconnect/RFID/rfid_scan.php" 
DEVICE_KEY = "PawConnect_RFID_2026_A1"  # must match the row you added to rfid_devices


def send_scan(chip_uid: str):
    try:
        resp = requests.post(
            SERVER_URL,
            json={"chip_uid": chip_uid, "device_key": DEVICE_KEY},
            timeout=5,
        )
        data = resp.json()
        if data.get("success"):
            pet = data.get("pet") or {}
            print(f"Scanned {chip_uid} -> {pet.get('name', 'unknown pet')}")
        else:
            print(f"Scanned {chip_uid} -> server said: {data.get('message')}")
    except requests.RequestException as e:
        print(f"Could not reach server: {e}")

def main():
    last_uid, last_time = None, 0.0
    DEBOUNCE_SECONDS = 3

    while True:
        try:
            with serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1) as ser:
                print(f"Listening on {SERIAL_PORT}...")
                while True:
                    line = ser.readline().decode("utf-8", errors="ignore").strip()
                    if not line:
                        continue

                    now = time.time()
                    if line != last_uid or now - last_time > DEBOUNCE_SECONDS:
                        last_uid, last_time = line, now
                        send_scan(line)
        except serial.SerialException as e:
            print(f"Serial error: {e} - retrying in 3s")
            time.sleep(3)


if __name__ == "__main__":
    main()