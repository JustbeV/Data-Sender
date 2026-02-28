"""
Firebase FCM Sender


Sends data messages via Firebase Cloud Messaging.
"""

import firebase_admin # type: ignore
from firebase_admin import credentials, db, messaging # type: ignore
from datetime import datetime

# Firebase Admin SDK
cred = credentials.Certificate("serviceAccountKey.json")
firebase_admin.initialize_app(cred, {
    'databaseURL': 'https://smartfireapp1-default-rtdb.firebaseio.com/'
})

# Fire event
event = {
    "timestamp": datetime.now().isoformat(),
    "temperature": 124.76,
    "smoke": 905,
    "status": "emergency"
}

# Save to Realtime Database
ref = db.reference("fire_events")
new_event = ref.push(event)
print("Event saved to Firebase:", new_event.key)

# Send FCM notification
message = messaging.Message(
    data={
        "temperature": str(event["temperature"]),
        "smoke": str(event["smoke"]),
        "status": event["status"],
        "body": f"Temp: {event['temperature']}°C | Smoke: {event['smoke']}"
    },
    topic="fire_alert"
)

response = messaging.send(message)
print("Data message sent, message ID:", response)