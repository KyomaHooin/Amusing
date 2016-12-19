
#include <VirtualWire.h>
#include <LowPower.h>
#include <EEPROM.h>
#include <DHT.h>

#define ledPin         13      // Status LED 
//#define radioDataPin   12      // Radio data
#define DHTPIN         11      // DHT pin
#define DHTPowerPin    10      // DHT power pin
#define radioPowerPin  9       // Radio power
#define sensorPowerPin 2       // Light sensor power
#define vLightPin      1
#define vSupplyPin     0

DHT dht(DHTPIN,DHT22);// DHT instance

// ----------------------------------------------------------------

void activeDHT() {
  pinMode(DHTPIN, INPUT);
  digitalWrite(DHTPowerPin,HIGH);
}

void passiveDHT() {
  digitalWrite(DHTPowerPin,LOW);
  pinMode(DHTPIN, OUTPUT);
  digitalWrite(DHTPIN, LOW);
}

void flash5ms() {
  digitalWrite(13,HIGH);
  delay(5);
  digitalWrite(13,LOW);
}

void serialMenu() {
  char c;
  Serial.println();   
  Serial.print("-- MENU --");
  Serial.println();
  Serial.println("Press 'a' to set radio address.");
  Serial.println("Press 'n' to set sleep cycle.");
  Serial.println("Press 'q' to quit.");
  while (c != 'q') {
    Serial.println();
    Serial.print("> ");
    while (!(Serial.available()));
    c = Serial.read();
    if ( c == 'a' ) { editRadioAddress(); }
    if ( c == 'n' ) { editSleepCycles(); }
  }
}

void editRadioAddress() {
  char c; 
  while (!(c >= 'A' && c <= 'Y')) {
    Serial.println();
    Serial.print("Address [A..Y]: ");
    while (!(Serial.available())); 
    c = Serial.read();
  }
  EEPROM.write(10,c);
  Serial.println();
  Serial.println("Done.");
}

void editSleepCycles() {
  byte n; 
  while (!(n >= 5 && n <= 255)) {
    Serial.println();   
    Serial.print("Cycle [5..255]: ");
    while (!(Serial.available())); 
    n = Serial.parseInt();
  }
  EEPROM.write(11,n);
  Serial.println();
  Serial.println("Done.");
}

// ----------------------------------------------------------------

void setup() {
  // Serial setup 
  Serial.begin(9600);  
  // Hardware setup
  pinMode(radioPowerPin,OUTPUT);
  digitalWrite(radioPowerPin,LOW);
  pinMode(sensorPowerPin,OUTPUT);
  digitalWrite(sensorPowerPin,LOW); 
  pinMode(DHTPowerPin,OUTPUT);
  digitalWrite(DHTPowerPin,LOW);
  pinMode(ledPin,OUTPUT);
  digitalWrite(ledPin,LOW);
  // Set up VirtualWire radio
  vw_setup(1000);// Bits per sec
  // AREF
  analogReference(INTERNAL);
  // Menu
  Serial.println("---- Picobeatle RINGO III, ver. 131207b ----");
  Serial.println();
  Serial.print("Radio address ["); Serial.print(EEPROM.read(10));
  Serial.print("] Sleep cycles ["); Serial.print(EEPROM.read(11));
  Serial.println("]");
  Serial.println();
  Serial.println("Press 's' for setup.");
  while (millis() < 3000) { if (Serial.read() == 's') { serialMenu(); }}
  Serial.println();
  Serial.println("Resuming normal operation."); 
}

// ----------------------------------------------------------------

void loop() {
  float vSupp, vLight, light, humidity, temperature;
  char msg[20], addr, sleepCycles;
  //Sensor & DHT on
  activeDHT();
  dht.begin();
  digitalWrite(sensorPowerPin,HIGH);
  // wait for VccH settle
  delay(500);
  // while initializing, read analog
  vSupp = analogRead(vSupplyPin) * 0.00457;
  vLight = analogRead(vLightPin) * 0.00457;
  humidity = dht.readHumidity();
  temperature = dht.readTemperature();
  // Sensor & DHT off
  digitalWrite(sensorPowerPin,LOW);
  passiveDHT();
  // light as percentage
  light = vLight/vSupp * 100;
  flash5ms();
  // create msg string
  addr = EEPROM.read(10);//radio address
  sprintf(msg, "*Z%c#T%03dH%03dL%03dB%03d", addr, temperature * 10, humidity * 10 , light * 10, vSupp * 100);   
  Serial.println(msg);
  // send out data message
  digitalWrite(radioPowerPin,HIGH);
  vw_send((uint8_t *)msg, strlen(msg));
  vw_wait_tx();
  digitalWrite(radioPowerPin,LOW);
  // Going to sleep..
  sleepCycles = EEPROM.read(11);
  Serial.print("Sleeping for "); Serial.print(sleepCycles); Serial.println(" * 8s cycle!");
  Serial.flush();// flush serial 
  // sleeping with LED flash
  for (int i = 0; i < sleepCycles; i++) {
    LowPower.powerDown(SLEEP_8S, ADC_OFF, BOD_OFF);
    flash5ms();
  }
}

