#!/usr/bin/python
#
# Reconfigure EIO channels as AIN and restore boot flash config.
#

import u3

jack = u3.U3()

jack.configU3(EIOAnalog=255)
jack.configIO(EIOAnalog=255)

jack.close()

