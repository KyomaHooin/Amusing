//
// RPi AVR 868.35 receiver & configurable LED PWM.
//

#include <VirtualWire.h>

#define pwmPin	3	
#define rxPin	7
#define ledPin	13

int n = 0;         // msg index
long intensity = -1;// fade PWM 0-255

// ----------------------------------------------------------------

void setup() {
  // Serial setup
  Serial.begin(9600);
  Serial.println("setup");
  // LED setup
  pinMode(ledPin,OUTPUT);  
  digitalWrite(ledPin,LOW);
  // PWM setup
  analogWrite(pwmPin,0);
  // VirtualWire setup
  vw_set_rx_pin(7);// RX pin
  vw_setup(1000);// Bits per sec
  vw_rx_start();// Start the receiver PLL running
  delay(1000);// ?
}

void loop() {
  uint8_t buf[VW_MAX_MESSAGE_LEN];
  uint8_t buflen = VW_MAX_MESSAGE_LEN;
  if (vw_get_message(buf, &buflen)) { // Non-blocking
    n++;// Message with a good checksum received, dump it
    digitalWrite(ledPin,HIGH);
    Serial.print("Got msg #");
    Serial.print(n);
    Serial.print(" : ");
    for (int i = 0; i < buflen; i++) { Serial.print(char(buf[i])); }
    Serial.println("");
    digitalWrite(ledPin,LOW);
  }
  if (Serial.available()) {// we have cmd in buffer
    if (Serial.read() == 'g') {// check first byte
      intensity = Serial.parseInt();// get value
    }
    if (intensity >= 0 && intensity <= 255) {
      analogWrite(pwmPin,intensity);
      Serial.print("Intensity ");
      Serial.print(intensity);
      Serial.println(" set.");
    }
  }
}

