
#include <VirtualWire.h>
//#include <LiquidCrystal.h>

int ledPin = 13;
int n = 0;// message index

//LiquidCrystal lcd(13, 12, 11, 10, 9, 8);// LCD initialize

// ----------------------------------------------------------------

void setup() {
  // Serial setup
  Serial.begin(9600);
  Serial.println("setup");
  // LED setup
  pinMode(ledPin,OUTPUT);  
  digitalWrite(ledPin,LOW);
  // VirtualWire setup
  vw_set_rx_pin(7);// RX pin
  vw_setup(1000);// Bits per sec
  vw_rx_start();// Start the receiver PLL running
  // LCD setup & greeting
  //lcd.begin(20, 4);
  //lcd.print("PicoBeatle RX");
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
    //lcd.clear();
    //lcd.print("Msg #");
    //lcd.print(n);
    //lcd.print(": ");
    //lcd.setCursor(0,1); 
    //for (int i = 0; i < buflen; i++) { lcd.print(char(buf[i])); } 
  }
}

