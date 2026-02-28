/**
 * Import function triggers from their respective submodules:
 *
 * const {onCall} = require("firebase-functions/v2/https");
 * const {onDocumentWritten} = require("firebase-functions/v2/firestore");
 *
 * See a full list of supported triggers at https://firebase.google.com/docs/functions
 */

const { setGlobalOptions } = require("firebase-functions");
// const {onRequest} = require("firebase-functions/https");
// const logger = require("firebase-functions/logger");

setGlobalOptions({ maxInstances: 10 });

const functions = require("firebase-functions");
const admin = require("firebase-admin");

admin.initializeApp();

exports.sendFireAlert = functions.database
  .ref("/alerts/{alertId}")
  .onCreate(async (snapshot, context) => {
    const alertData = snapshot.val();
    const status = alertData.status || "unknown";

    let title = "Fire Alert System";
    let body = "";

    if (status === "fire") {
      title = "🔥 FIRE DETECTED!";
      body = "Flame sensor triggered!";
    } else if (status === "smoke") {
      title = "💨 SMOKE DETECTED!";
      body = "Smoke sensor triggered!";
    } else if (status === "normal") {
      title = "✅ SAFE";
      body = "Environment back to normal.";
    }

    const message = {
      notification: { title, body },
      data: {
        status: status,
        timestamp: String(alertData.timestamp || Date.now()),
      },
      topic: "fireAlerts",
    };

    try {
      const response = await admin.messaging().send(message);
      console.log("✅ Notification sent:", response);
    } catch (error) {
      console.error("❌ Error sending notification:", error);
    }
  });
