
#include <VirtualWire.h>
#include <LowPower.h>
#include <EEPROM.h>
#include <Wire.h>
#include <DHT.h>

#define ledPin         13      // Status LED 
//#define radioDataPin   12    // Radio data
#define DHTPIN         11      // DHT pin
#define DHTPowerPin    10      // DHT power pin
#define radioPowerPin  9       // Radio power
#define sensorPowerPin 2       // Light sensor power
#define vLightPin      1
#define vSupplyPin     0

DHT dht(DHTPIN,DHT22);// DHT instance

int sleepCycles, sleepCyclesNow;// default count of 8 second seep cycles

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
  pinMode(ledPin,OUTPUT);
  digitalWrite(13,HIGH);
  delay(5);
  digitalWrite(13,LOW);
}

boolean establishContact() {
  byte loopCount = 0;
  while (loopCount <= 10) {
    if(Serial.read() == 's') { return 1; } 
    delay(300);
    loopCount++;
  }
}

void serialMenu() {
  char c;
  while (c != 'q') {
    Serial.println();   
    Serial.print("SETUP MENU, [q] for quit >>> ");
    Serial.println();
    Serial.println(" 'a' - Setup radio address.");
    Serial.println(" 'n' - Setup number of sleep cycles.");
    while (!(Serial.available()));
    c = Serial.read();
    switch (c) {
      case 'a':
        editRadioAddress();
        break;
      case 'n':
        editSleepCycles();
        break;
    }
  }
}

void editRadioAddress() {
  char c; 
  Serial.println();   
  while (c != 'q') {
    Serial.print("Press a key [A..Y], or [q] to quit >>> ");
    while (!(Serial.available())); 
    c = Serial.read();
    if ((c >= 'A') && (c <= 'Y')) {
      EEPROM.write(10,c);
      Serial.println("New radio address set.");
      return;
    } 
  }
}

void editSleepCycles() {
  byte n; 
  Serial.println();   
  while (n != 'q') {
    Serial.print("Enter a new value [5..255], or [q] to quit >>> ");
    while (!(Serial.available())); 
    n = Serial.parseInt();
    if ((n >= 5) && (n <= 255)) {
      EEPROM.write(11,n);
      Serial.println("New sleep cycle count set.");
      return;
    }
  }
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
  Serial.print("Radio address: "); Serial.println(EEPROM.read(10));
  Serial.print("Sleep cycles: "); Serial.println(EEPROM.read(11));
  Serial.println();
  Serial.println("Press [s] to enter setup mode.");
  if (establishContact()) { serialMenu(); }
  Serial.println();
  Serial.println("Resuming normal operation, power cycle for menu ..."); 
  sleepCycles = EEPROM.read(11);
}

// ----------------------------------------------------------------

void loop() {
  float vSupp, vLight, humidity, temperature;
  char msg[20];
  //DHT on
  activeDHT();
  dht.begin();
  // Sensor on
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
  float light = vLight/vSupp * 100;
  // Serial printout
  //Serial.print("Temperature = "); Serial.print(temperature); Serial.println(" *C");
  //Serial.print("Humidity = "); Serial.print(humidity); Serial.println(" %");
  //Serial.print("Light = "); Serial.print(light); Serial.println(" %");
  //Serial.print("Vcc = "); Serial.print(vSupp); Serial.println(" V");
  //Serial.print("Light V = "); Serial.print(vLight); Serial.println(" V");
  flash5ms();
  // create msg string
  char addr = EEPROM.read(10);// address
  sprintf(msg, "*Z%c#T%03dH%03dL%03dB%03d", addr, temperature * 10, humidity * 10 , light * 10, vSupp * 100);   
  Serial.println(msg);
  Serial.println();  
  // send out data message
  digitalWrite(radioPowerPin,HIGH);
  vw_send((uint8_t *)msg, strlen(msg));
  vw_wait_tx();
  digitalWrite(radioPowerPin,LOW);
  // calculate the sleeping parameters
  sleepCyclesNow = sleepCycles + random(-2,3);
  Serial.print("Sleeping for ");
  Serial.print(sleepCyclesNow);
  Serial.println(" 8-sec periods!");
  Serial.flush(); // flush serial 
  // sleeping with LED flash
  for (int i = 0; i < sleepCyclesNow; i++) {
    LowPower.powerDown(SLEEP_8S, ADC_OFF, BOD_OFF);
    flash5ms();
  }
}

