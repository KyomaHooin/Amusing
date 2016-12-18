
#include "Wire.h"
#include <VirtualWire.h>
#include <avr/wdt.h>
#include <avr/sleep.h>
#include <avr/power.h>
#include "DHT.h"
#include <EEPROM.h>

// digital pins
#define DHTPIN         11      // what pin is the DHT connected to
#define DHTTYPE        DHT22   // DHT 11 used
#define DHTpowerPin    10      // power supply pin for DHT
#define sensorPowerPin 2       // sensor power switch
#define radioPowerPin  9       // radio power switch
//#define radioDataPin   12
#define ledPin         13      // status LED switch 
// analog pins
#define vSupplyPin     0
#define vLightPin      1

DHT dht(DHTPIN, DHTTYPE);

// global parameters
int sleepCycles, sleepCyclesNow, dhtType, nSends = 0;// default count of 8 second seep cycles

// ----------------------------------------------------------------

// define cbi() and sbi() for SFRs
#ifndef cbi
#define cbi(sfr, bit) (_SFR_BYTE(sfr) &= ~_BV(bit))
#endif
#ifndef sbi
#define sbi(sfr, bit) (_SFR_BYTE(sfr) |= _BV(bit))
#endif

// ----------------------------------------------------------------

void activeDHT() {
  pinMode(DHTPIN, INPUT);
  digitalWrite(DHTpowerPin,HIGH);
}

void passiveDHT() {
  digitalWrite(DHTpowerPin,LOW);
  pinMode(DHTPIN, OUTPUT);
  digitalWrite(DHTPIN, LOW);
}

// ----------------------------------------------------------------

void vytuhni8sec() {
  // clear various "reset" flags
  MCUSR = 0;     
  // allow changes, disable reset
  WDTCSR = _BV (WDCE) | _BV (WDE);
  // set interrupt mode and an interval 
  WDTCSR = _BV (WDIE) | _BV (WDP3) | _BV (WDP0);    // set WDIE, and 8 seconds delay
  wdt_reset();  // pat the dog  
  // set sleep mode & enable sleep  
  set_sleep_mode (SLEEP_MODE_PWR_DOWN);  
  sleep_enable();
  // turn off brown-out enable in software
  MCUCR = _BV (BODS) | _BV (BODSE);
  MCUCR = _BV (BODS);
  // go to sleep  
  sleep_cpu();  
  // cancel sleep as a precaution
  sleep_disable();  
} 

// ----------------------------------------------------------------

void flash20ms() {
  // be sure the port is output
  pinMode(ledPin,OUTPUT);
  // light up alive flashlight
  digitalWrite(13,HIGH);
  delay(5);
  // turn off flashlight
  digitalWrite(13,LOW);
}

boolean establishContact() {
  int loopCount = 0;
  while (loopCount <= 10) {
    if(Serial.read() == 's') { return 1; } 
    delay(300);
    loopCount++;
  }
}

void serialMenu() {
  char c;
  while (c != 'g') {
    Serial.println();   
    Serial.print("SETUP MENU ('h' for help) >>> ");
    while (!(Serial.available()));
    c = Serial.read();
    switch (c) {
      case 'a':
        editRadioAddress();
        break;
      case 'n':
        editSleepCycles();
        break;
      //case 'd':
        //toggleDHT();
        //break;
      case 'h':
        printHelp();
        break;     
    }
  }
}

void printHelp() {
  showTitle();
  Serial.println();
  Serial.println("Commands help:");
  Serial.println(" 'a' to set radio address of this sensor");
  Serial.println(" 'n' to set count of 8-seconds sleep cycles");
  //Serial.println(" 'd' to toggle DHT sensor type");
  Serial.println(" 'h' to display help & settings");
  Serial.println(" 'g' to start normal operation");
} 

void editRadioAddress() {
  char c = EEPROM.read(10); 
  Serial.println();   
  Serial.print("Current radio address [");
  Serial.print(c);
  Serial.println("].");
  while (c != 'q') {
    Serial.print("Press a key [A..Y], or [q] to quit >>> ");
    while (!(Serial.available())); 
    c = Serial.read();
    Serial.println(c);
    if ((c >= 'A') && (c <= 'Y')) {
      EEPROM.write(10,c);
      Serial.println("New radio address set.");
      return;
    } 
  }
}

void editSleepCycles() {
  byte n = EEPROM.read(11); 
  Serial.println();   
  Serial.print("Current number of sleep cycles [");
  Serial.print(n);
  Serial.println("].");
  while (n != 'q') {
    Serial.print("Enter a new value [5..255], or [q] to quit >>> ");
    while (!(Serial.available())); 
    n = Serial.parseInt();
    Serial.println(n);
    if ((n >= 5) && (n <= 255)) {
      EEPROM.write(11,n);
      Serial.println("New sleep cycle count set.");
      return;
    }
  }
}

void toggleDHT() {
  char c, n;
  n = EEPROM.read(12); 
  Serial.println();   
  Serial.print("Current DHT type setting [");
  n == 1 ? Serial.print("DHT11") : Serial.print("DHT21");
  Serial.println("].");
  while (c != 'q') {
    Serial.print("Press [t] to toggle, or [q] to quit >>> ");
    while (!(Serial.available())); 
    c = Serial.read();
    Serial.println(c);
    switch (c) {
      case 't':
        n == 0 ? n = 1 : n = 0;
        EEPROM.write(12,n);
        Serial.println("New DHT type set. [");
        return;
        break;
      case 'q':
        break;
    }    
  }
}

void showTitle()
{
  char n, c;
  c = EEPROM.read(10); 
  Serial.println("---- Picobeatle RINGO III, ver. 131207a -----");   
  Serial.print("Current radio address [");
  Serial.print(c);
  Serial.println("]");
  Serial.print("Current number of sleep cycles [");
  n = EEPROM.read(11);
  Serial.print(n);
  Serial.println("]");
  //n = EEPROM.read(12); 
  //Serial.print("Current DHT type setting [");
  //n == 1 ? Serial.print("DHT11") : Serial.print("DHT21");
  //Serial.println("].");
}

// ----------------------------------------------------------------

ISR(WDT_vect) { wdt_disable(); };             // just disable watchdog 

void setup() {
  // set up serial channel 
  Serial.begin(9600);  
  // hardware control pins, modes
  pinMode(radioPowerPin,OUTPUT);
  pinMode(sensorPowerPin,OUTPUT);
  pinMode(DHTpowerPin,OUTPUT);
  // set up VirtualWire radio
  vw_setup(1000); // Bits per sec

  analogReference(INTERNAL);
  //LED setup
  pinMode(ledPin,OUTPUT);
  digitalWrite(ledPin,LOW);
  // turn radio & sensor power off now, until needed
  digitalWrite(radioPowerPin,LOW);
  digitalWrite(sensorPowerPin,LOW);
 
  showTitle();
  Serial.println("Press 's' to enter setup mode");

  if (establishContact()) { serialMenu(); }

  Serial.println();
  Serial.println("Resuming the normal operation, power cycle for menu ..."); 

  sleepCycles = EEPROM.read(11);  

}

// ----------------------------------------------------------------

void loop() {
  float vSupp, vLight, hh, tt;

  dht.begin();

  // turn on sensor power
  digitalWrite(sensorPowerPin,HIGH);
  // wait for VccH settle
  delay(500);
  // while initializing, read analogues
  vSupp = analogRead(vSupplyPin)*0.00457;
  vLight = analogRead(vLightPin)*0.00457;

  if (nSends > 0) {
    hh = dht.readHumidity();
    tt = dht.readTemperature();
  }  
  // turn off sensor & DHT power
  digitalWrite(sensorPowerPin,LOW);
  passiveDHT();
  // light as percentage
  float ll = vLight/vSupp*100;

  flash20ms();

  // Serial printout
  if (nSends > 0) {
     Serial.print("Temperature = ");
     Serial.print(tt);
     Serial.println(" *C");
     Serial.print("Humidity = ");
     Serial.print(hh);
     Serial.println(" %");
  }
   
  Serial.print("Light = ");
  Serial.print(ll);
  Serial.println(" %");
  Serial.println(" --- internals ---");
  Serial.print("Vcc = ");
  Serial.print(vSupp);
  Serial.println(" V");
  Serial.print("Light V = ");
  Serial.print(vLight);
  Serial.println(" V");

  if (nSends < 4) {
    Serial.print("Test phase active (");
    Serial.print(nSends);
    Serial.println(")");
  }

  flash20ms();

  // set up data message
  char msg[20];
  int t, p, h, l, b;
  char a;  
  // fix up numbers for no-decimal-point xfer 
  t = 10*tt;
  h = 10*hh;
  l = 10*ll;
  b = 100*vSupp;

  // create msg string
  a = EEPROM.read(10);
  if (nSends > 0) {
    sprintf(msg, "*Z%c#T%03dH%03dL%03dB%03d", a, t, h, l, b);
  } else {
     sprintf(msg, "*Z%c#B%03dU", a, b);
  }
   
  Serial.println(msg);
  Serial.println();

  // send out data message
  digitalWrite(radioPowerPin,HIGH);
  vw_send((uint8_t *)msg, strlen(msg));
  vw_wait_tx();
  digitalWrite(radioPowerPin,LOW);

  // calculate the sleeping parameters
  if (nSends >= 4) {
    sleepCyclesNow = sleepCycles + random(-2,3);
  } else {
    sleepCyclesNow = 8;
    nSends++;
  }
  Serial.print("Sleeping for ");
  Serial.print(sleepCyclesNow);
  Serial.println(" 8-sec periods. G'nite!");
  Serial.println();
  Serial.flush(); // wait till serial data sent 

  // stop ADC
  cbi(ADCSRA,ADEN);
  // move I2C pins to minimal consumption
  pinMode(A4, OUTPUT);
  digitalWrite(A4, LOW);
  pinMode(A5, OUTPUT);
  digitalWrite(A5, LOW);

  // sleeping with led flashes
  for (int i=1; i<=sleepCyclesNow; i++) {
    vytuhni8sec();
    flash20ms();
    if (i == (sleepCyclesNow - 1)) { activeDHT(); } 
  }

  // return I2C pins to default
  pinMode(A4, INPUT);  
  pinMode(A5, INPUT);
  // start ADC
  sbi(ADCSRA,ADEN);
}

