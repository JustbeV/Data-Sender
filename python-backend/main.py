"""
Firebase Data Sender

Sends fire events to Firebase Realtime Database.
"""

import firebase_admin # type: ignore
from firebase_admin import credentials, db # type: ignore
from datetime import datetime

# Firebase Admin SDK (private key should NOT be uploaded)
cred = credentials.Certificate("serviceAccountKey.json")
firebase_admin.initialize_app(cred, {
    'databaseURL': 'https://smartfireapp1-default-rtdb.firebaseio.com/'
})

# Fire event
event = {
    "timestamp": datetime.now().isoformat(),
    "temperature": 122.75,  # example high value
    "smoke": 988,            # example dangerous value
    "status": "emergency"
}

# Save to Realtime Database
ref = db.reference("fire_events")
new_event = ref.push(event)

print("EMERGENCY Event saved to Firebase!", new_event.key)