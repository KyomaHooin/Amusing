//Author: LabJack
//April 7, 2008
//This examples demonstrates how to read from analog inputs (AIN) and digital
//inputs(FIO), set analog outputs (DAC) and digital outputs (FIO), and how to
//configure and enable timers and counters and read input timers and counters
//values using the "easy" functions.

#include "u3.h"
#include <unistd.h>

int main(int argc, char **argv)
{
    HANDLE hDevice;
    u3CalibrationInfo caliInfo;
    int localID;
    long DAC1Enable, error;

    //Open first found U3 over USB
    localID = -1;
    if( (hDevice = openUSBConnection(localID)) == NULL )
        goto done;

    //Get calibration information from U3
    if( getCalibrationInfo(hDevice, &caliInfo) < 0 )
        goto close;


    /* Note: The eAIN, eDAC, eDI, and eDO "easy" functions have the ConfigIO
       parameter.  If calling, for example, eAIN to read AIN3 in a loop, set the
       ConfigIO parameter to 1 (True) on the first iteration so that the
       ConfigIO low-level function is called to ensure that channel 3 is set to
       an analog input.  For the rest of the iterations in the loop, set the
       ConfigIO parameter to 0 (False) since the channel is already set as
       analog. */


    /* Note: The eAIN "easy" function has the DAC1Enable parameter that needs to
       be set to calculate the correct voltage.  In addition to the earlier
       note, if running eAIN in a loop, set ConfigIO to 1 (True) on the first
       iteration to also set the output of the DAC1Enable parameter with the
       current setting on the U3.  For the rest of the iterations, set ConfigIO
       to 0 (False) and use the outputted DAC1Enable parameter from the first
       interation from then on.  If DAC1 is enabled/disabled from a later eDAC
       or ConfigIO low-level call, change the DAC1Enable parameter accordingly
       or make another eAIN call with the ConfigIO parameter set to 1. */

    //Read the single-ended voltage from AIN3
    double dblVoltage;


    if( (error = eAIN(hDevice, &caliInfo, 1, &DAC1Enable, 8, 31, &dblVoltage, 0, 0, 0, 0, 0, 0)) != 0 )
        goto close;
    printf("CH01 value = %.5f\n", dblVoltage);

    if( (error = eAIN(hDevice, &caliInfo, 1, &DAC1Enable, 9, 31, &dblVoltage, 0, 0, 0, 0, 0, 0)) != 0 )
        goto close;
    printf("CH02 value = %.3f\n", dblVoltage);

    if( (error = eAIN(hDevice, &caliInfo, 1, &DAC1Enable, 10, 31, &dblVoltage, 0, 0, 0, 0, 0, 0)) != 0 )
        goto close;
    printf("CH03 value = %.3f\n", dblVoltage);

    if( (error = eAIN(hDevice, &caliInfo, 1, &DAC1Enable, 11, 31, &dblVoltage, 0, 0, 0, 0, 0, 0)) != 0 )
        goto close;
    printf("CH04 value = %.3f\n", dblVoltage);



    if( (error = eAIN(hDevice, &caliInfo, 1, &DAC1Enable, 12, 31, &dblVoltage, 0, 0, 0, 0, 0, 0)) != 0 )
        goto close;
    printf("CH05 value = %.3f\n", dblVoltage);

    if( (error = eAIN(hDevice, &caliInfo, 1, &DAC1Enable, 13, 31, &dblVoltage, 0, 0, 0, 0, 0, 0)) != 0 )
        goto close;
    printf("CH06 value = %.3f\n", dblVoltage);

    if( (error = eAIN(hDevice, &caliInfo, 1, &DAC1Enable, 14, 31, &dblVoltage, 0, 0, 0, 0, 0, 0)) != 0 )
        goto close;
    printf("CH07 value = %.3f\n", dblVoltage);

    if( (error = eAIN(hDevice, &caliInfo, 1, &DAC1Enable, 15, 31, &dblVoltage, 0, 0, 0, 0, 0, 0)) != 0 )
        goto close;
    printf("CH08 value = %.3f\n", dblVoltage);






close:
    if( error > 0 )
        printf("Received an error code of %ld\n", error);
    closeUSBConnection(hDevice);
done:
    return 0;
}
