#!/usr/bin/python
import glob
import logging
import os
import sys
import time

import climate_export as CEX

# __________ Global Settings _______________
IN_DIRNAME = os.path.normpath("in_pracom_voltcraft_xls")
file_ext = "xls"
LOG_DIR = os.path.join(IN_DIRNAME, "log")
SENT_DIR = os.path.join(IN_DIRNAME, "sent")

RUN_TIMESTAMP = time.strftime("%Y%m%dT%H%M%S")

LOGGING_LEVEL = logging.INFO
# LOGGING_LEVEL = logging.DEBUG
LOG_FNAME = os.path.join(LOG_DIR, RUN_TIMESTAMP + ".log")

FTP_HOST = "amusing.itam.cas.cz"
FTP_LOGIN = "prachatice"
FTP_PW = "p3chk76f"
REMOTE_FTP_DIR = "/"

ZIP_FNAME = os.path.normpath(IN_DIRNAME + "/" + "pracom-voltcraft-" + RUN_TIMESTAMP + ".zip")

# _____________ Sensor mapping______________
SENSOR_DICT = {
    "Data1_": "97",  # ser.no  pracom 1
    "Data2_": "98",  # ser.no  pracom 2
    "Data3_": "99",  # ser.no  pracom 3
    "Data4_": "100",  # ser.no  pracom 4
    "Data5_": "101",  # ser.no  pracom 5
    "Data6_": "102",  # ser.no  pracom 6
    "Data7_": "103",  # ser.no  pracom 7
    "Data8_": "104",  # ser.no  pracom 8
    "Data_1": "97",  # ser.no  pracom 1
    "Data_2": "98",  # ser.no  pracom 2
    "Data_3": "99",  # ser.no  pracom 3
    "Data_4": "100",  # ser.no  pracom 4
    "Data_5": "101",  # ser.no  pracom 5
    "Data_6": "102",  # ser.no  pracom 6
    "Data_7": "103",  # ser.no  pracom 7
    "Data_8": "104",  # ser.no  pracom 8
}


def main():
    CEX.start_logging(LOG_FNAME, LOGGING_LEVEL)
    # ____________  convert  ________________________________
    os.makedirs(IN_DIRNAME, exist_ok=True)
    logging.info("Converting files in:\t%s", IN_DIRNAME)
    converted_count = 0
    fnames = glob.glob(os.path.join(IN_DIRNAME, "*." + file_ext))
    if not fnames:
        logging.info("\tno files with ext:\t%s\t%s", IN_DIRNAME, file_ext)
        sys.exit()
    for fname in fnames:
        logging.debug("file found:\t%s", fname)
        fname_part = os.path.split(fname)[1][0:6]
        sensor_id = SENSOR_DICT.get(fname_part)  		# get sensor id from Sensor mapping
        CEX.voltcraft_xls2tsv(fname, sensor_id)
        try:
            os.remove(fname)
            pass
        except OSError:
            logging.warning("file could not be deleted:\t%s", fname)
        else:
            logging.debug("\tdeleted:\t%s", fname)
            converted_count += 1
    logging.info("\tcount:\t%s", converted_count)

    # ____________  create zip and delete files ________________________________
    CEX.zip_n_del_files(ZIP_FNAME, IN_DIRNAME, "tsv")

    # ____________  upload to ftp ________________________________
    if CEX.agree_to_upload(ZIP_FNAME):
        CEX.upload2ftp_and_archive(IN_DIRNAME, "zip", SENT_DIR, FTP_HOST, FTP_LOGIN, FTP_PW)

    # ____________  Clean up ________________________________
    CEX.delete_old_files(SENT_DIR, 240)  # delete sent zip files older than x hours
    CEX.delete_old_files(LOG_DIR, 240)  
    # ____________  Finish ________________________________
    CEX.stop_logging(LOG_FNAME)
    os.system(LOG_FNAME)		# open log file


if __name__ == "__main__":
    main()
