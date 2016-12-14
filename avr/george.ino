#include <LiquidCrystal.h>
#include <VirtualWire.h>

int ledPin=13;
int n=0;

// setup LCD output
// initialize the library with the numbers of the interface pins
//LiquidCrystal lcd(13, 12, 11, 10, 9, 8);



void setup()
{

pinMode(ledPin,OUTPUT);  
digitalWrite(ledPin,LOW);
  
// setup serial output
Serial.begin(9600);
Serial.println("setup");

// setup VirtualWire
vw_setup(1000); // Bits per sec
vw_rx_start(); // Start the receiver PLL running
vw_set_rx_pin(7);

//LCD greeting
//lcd.begin(20, 4);
// Print a message to the LCD.
//lcd.print("PicoBeatle RX");
//lcd.print("SENSOR:01 MSG:05DATA:T21.6H52.7         PRENOS:OK");
delay(1000);


}
void loop()
{
uint8_t buf[VW_MAX_MESSAGE_LEN];
uint8_t buflen = VW_MAX_MESSAGE_LEN;

if (vw_get_message(buf, &buflen)) // Non-blocking
{
 int i;
 // Message with a good checksum received, dump it
 n++;

 digitalWrite(ledPin,HIGH);
 Serial.print("Got msg #");
 Serial.print(n);
 Serial.print(" : ");
 for (i = 0; i < buflen; i++)
  {
   Serial.print(char(buf[i]));
   }
 Serial.println("");
 digitalWrite(ledPin,LOW);
// lcd.clear();
// lcd.print("Msg #");
// lcd.print(n);
// lcd.print(": ");
// lcd.setCursor(0,1); 
// for (i = 0; i < buflen; i++)
//  {
//   lcd.print(char(buf[i]));
//   } 
}




}