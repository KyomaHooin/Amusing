#!/usr/bin/python
import csv
import datetime
import ftplib
import logging
import os
import shutil
import sys
import time
import traceback
import zipfile

from dbfread import DBF

# ========== Global Settings ===============
DBF_DIR = os.path.normpath("c:/PRACHATICE-CLIMATE-EXPORT/dbf")
TSV_DIR = os.path.normpath("c:/PRACHATICE-CLIMATE-EXPORT/tsv")
TO_SEND_DIR = os.path.normpath("c:/PRACHATICE-CLIMATE-EXPORT/to_send")
SENT_DIR = os.path.normpath("c:/PRACHATICE-CLIMATE-EXPORT/sent")
LOG_DIR = os.path.normpath("c:/PRACHATICE-CLIMATE-EXPORT/log")

RUN_TIMESTAMP = time.strftime("%Y%m%dT%H%M%S")
LOGGING_LEVEL = logging.INFO
LOGGING_LEVEL = logging.DEBUG
LOG_FILE = os.path.join(LOG_DIR, RUN_TIMESTAMP + ".log")
HOURS_TO_LIVE=24*30		# vymazat exportovaná data po 30 dnech

FTP_HOST = "[removed]"
FTP_LOGIN = "[removed]"
FTP_PW = "[removed]"
REMOTE_FTP_DIR = "/"

# ------------- Sensor mapping--------------
""" přiřazení senzorů probíhá podle počtu senzorů v souboru a jejich pořadí.
Záhlaví sloupců ve zdrojovém DBF bohužel není unikátní."""
sensor_dict = {
    "61": "53",  # ser.no  06030003-0102
    "62": "54",  # ser.no  06030003-0304
    "63": "55",  # ser.no  06030003-0506
    "64": "56",  # ser.no  06030003-0708
    "65": "57",  # ser.no  06030003-0910
    "66": "58",  # ser.no  06030003-1112
    "81": "45",  # ser.no  06030008-0102
    "82": "46",  # ser.no  06030008-0304
    "83": "47",  # ser.no  06030008-0506
    "84": "48",  # ser.no  06030008-0708
    "85": "49",  # ser.no  06030008-0910
    "86": "50",  # ser.no  06030008-1112
    "87": "51",  # ser.no  06030008-1314
    "88": "52",  # ser.no  06030008-1516

}


def start_logging(log_fname, logging_level):
    # =========== setup logging ==============
    os.makedirs(os.path.split(log_fname)[0], exist_ok=True)
    logging.basicConfig(level=logging_level, format="%(asctime)s\t%(levelname)s\t%(message)s",
                        handlers=[logging.FileHandler(log_fname),
                                  logging.StreamHandler()])
    logging.info("Program started:\t%s", __file__)

def stop_logging(log_fname):
    # =========== stop logging ==============
    logging.info("Program finished, logfile:\t%s", log_fname)
    logging.shutdown()

def get_recursive_file_list(in_dirname, file_ext):
    """Get list of files with given extension in directory recursively, ignore case."""
    fl = [os.path.join(r, f)
        for r, ds, fs in os.walk(in_dirname)
        for f in fs if f.lower().endswith(file_ext.lower())]
    #print("get_recursive_file_list:\t%s", fl)
    return fl

def get_file_isotimestamp(in_fname):
    f_mod = datetime.datetime.fromtimestamp(os.path.getmtime(in_fname))
    return f_mod.strftime("%Y%m%dT%H%M%S")

def split_dbf(dbf_in, out_dir):
    """
    Split DBF columns into multiple CSV (one per sensor) into same folder and delete source DBF
    :param out_dir: 
    :param dbf_in: 
    """
    csvout_header = ["datetime-gmt+1", "temperature", "humidity"]
    csvout_delimit = "\t"
    table = DBF(dbf_in, encoding="Windows-1250")
    sensor_count = int((len(table.field_names) - 3) / 2)
    # ========== log DBF info ==============
    logging.info("\tconverting DBF:\t%s", dbf_in)
    logging.debug("\tsensors=%s", sensor_count)
    logging.debug("\trecords=%s", len(table))
    logging.debug("\tDBF format=%s", table.dbversion)
    logging.debug("\theader=%s", table.field_names)
    logging.debug("\tencoding=%s", table.encoding)
    # ======== check if input file is correct ========
    if not (sensor_count == 6 or sensor_count == 8) or table.field_names[0] != "DATUM":
        logging.info("\t\tIncorrect DBF format\t%s (header:%s)", dbf_in, table.field_names)
        return 0

    # ========== read and write columns to multiple csv files ==============
    for sensor_no in range(sensor_count):
        sensor_header = table.field_names[
            3 + sensor_no * 2]  # DBF z Prachatic má 3 sloupce (datum,čas,výpadek) a 2 sloupce pro každý senzor
        sensor_id = sensor_dict.get(str(sensor_count) + str(sensor_no + 1))  # get sensor id from settings
        csv_out = os.path.join(out_dir, get_file_isotimestamp(dbf_in) + "_" + sensor_id + ".tsv")  # CSV output file
        logging.info("\t\tWriting\t%s (header:%s)", csv_out, sensor_header)
        ocsv_out = csv.writer(open(csv_out, "w+"), delimiter=csvout_delimit, lineterminator="\n")
        ocsv_out.writerow(csvout_header)
        for record in table:
            rec_date = list(record.values())[0]
            rec_timestr = list(record.values())[1]
            rec_time = datetime.datetime.strptime(rec_timestr, "%H:%M:%S").time()
            rec_datetime = datetime.datetime.combine(rec_date, rec_time)
            rec_datetime_str = rec_datetime.strftime("%Y%m%dT%H%M%S")
            rec_temp = list(record.values())[3 + sensor_no * 2]
            rec_humi = list(record.values())[4 + sensor_no * 2]
            # print(rec_date, type(rec_date), rec_time, type(rec_time), rec_datetime_str, rec_temp, type(rec_temp),rec_humi,type(rec_humi)
            csvout_data_row = list([rec_datetime_str, rec_temp, rec_humi])
            # print(out_row)
            # print(list(record.values())[1])
            ocsv_out.writerow(csvout_data_row)
    return sensor_count

def zip_n_del_files(zip_fname, dirname, file_ext):
    """
    Creates zip archive from folder (non-recursively), files can be filtered by extension
    :param file_ext:
    :param dirname:
    :param zip_fname:
    """
    logging.info("Creating Zip, source:\t%s\t%s\t%s", zip_fname, dirname, file_ext)
    file_count = 0
    # ============ get files list ==============
    fnames = get_recursive_file_list(dirname, file_ext)
    if fnames:
        with zipfile.ZipFile(zip_fname, "w", zipfile.ZIP_DEFLATED) as z:
            for fname in fnames:
                try:
                    z.write(os.path.join(dirname, fname), arcname=os.path.basename(fname))
                    os.remove(fname)
                except Exception as e:
                    logging.error(traceback.format_exc())
                else:
                    file_count += 1
                    logging.info("\tfile moved:\t%s", fname)
    else:
        logging.info("\tNo files:\t%s\t%s", dirname, file_ext)
    return file_count

def upload2ftp_and_archive(in_dirname, file_ext, archive_dirname, ftp_host, ftp_login="anonymous", ftp_pw="anonymous",
                           remote_ftp_dir="/"):
    """
    Upload files with given extension to ftp and move them to archive
    """
    logging.info("Uploading to ftp:\t%s", ftp_host)
    uploaded_count = 0
    # ============ get DBF files list ==============
    fnames = [os.path.join(r, f)
        for r, ds, fs in os.walk(in_dirname)
        for f in fs if f.endswith(file_ext)]               #get list of files with extension in directory recursively
    #print(fnames)
    if not fnames:
        logging.info("\tnothing uploaded - no files with ext:\t%s\t%s", in_dirname, file_ext)
        return uploaded_count
    os.makedirs(archive_dirname, exist_ok=True)
    ftp = ftplib.FTP(ftp_host, ftp_login, ftp_pw)
    # ftp.set_debuglevel(2)
    ftp.cwd(remote_ftp_dir)
    for fname in fnames:
        fbase = os.path.basename(fname)
        with open(fname, "rb") as f_obj:
            try:
                ftp.storbinary("STOR %s" % fbase, f_obj)
            except ftplib.all_errors as e:
                error_code_string = str(e).split(None, 1)
                logging.error("\tupload failed:\t%s\t%s", fname, error_code_string)
            else:
                logging.info("\tupload ok:\t%s", fname)
                f_obj.close()
                try:  # after upload move file to archive (overwrite if exists)
                    shutil.move(fname, os.path.join(archive_dirname, fbase))
                except Exception as e:
                    logging.error(traceback.format_exc())
    ftp.quit()
    return uploaded_count

def delete_old_files_recursively(dirname, hours_to_live=24, file_ext=""):
    """
    Delete all old files from archive, return deleted count.
    :param hours_to_live:
    :param dirname:
    """
    logging.info("Deleting old files in:\t%s", dirname)
    if not os.path.isdir(dirname):
        return 0
    deleted_count = 0
    fnames = get_recursive_file_list(dirname, file_ext)
    for fname in fnames:
        file_modified = datetime.datetime.fromtimestamp(os.path.getmtime(fname))
        if datetime.datetime.now() - file_modified > datetime.timedelta(**{"hours": hours_to_live}):
            try:
                os.remove(fname)
            except Exception as e:
                logging.error(traceback.format_exc())
            else:
                deleted_count += 1
                #print("deleted", fname)
                logging.debug("\tdeleted:\t%s", fname)
    logging.info("\tdeleted_count:\t%s", deleted_count)
    return deleted_count

def main():
    # ============ Make dirs if not exist ============== (works from python 3.2)
    os.makedirs(LOG_DIR, exist_ok=True)
    os.makedirs(TSV_DIR, exist_ok=True)
    os.makedirs(TO_SEND_DIR, exist_ok=True)
    os.makedirs(SENT_DIR, exist_ok=True)

    #  ============ Setup logging ==============
    start_logging(LOG_FILE, LOGGING_LEVEL)

    # ============ Get DBF files list ==============
    dbf_fnames = get_recursive_file_list(DBF_DIR, ".dbf")
    if not dbf_fnames:
        logging.info("No dbf files found in:\t%s", DBF_DIR)
        logging.info("Program finished")
        sys.exit(0)

    # ============  Split DBF  ================================
    logging.info("Converting DBF files in:\t%s", DBF_DIR)
    file_count = 0
    for fname in dbf_fnames:
        logging.debug("\t%s", fname)
        try:
            split_dbf(fname, TSV_DIR)
            os.remove(fname)
        except OSError:
            logging.warning("DBF could not be deleted:\t%s", fname)
        else:
            logging.info("\tDBF deleted:\t%s", fname)
        file_count += 1
    logging.info("\tcount:\t%s", file_count)

    # ============  Zip TSV  ================================
    if os.listdir(TSV_DIR):
        zip_fname = os.path.normpath(TO_SEND_DIR + "/" + "prachatice-" + RUN_TIMESTAMP + ".zip")
        zip_n_del_files(zip_fname, TSV_DIR, file_ext=".tsv")

    # ============  Upload to ftp ================================
    if os.listdir(TO_SEND_DIR):
        upload2ftp_and_archive(TO_SEND_DIR, "zip", SENT_DIR, FTP_HOST, FTP_LOGIN, FTP_PW,REMOTE_FTP_DIR)

    # ============  Clean up ================================
    delete_old_files_recursively(DBF_DIR, hours_to_live=HOURS_TO_LIVE)
    delete_old_files_recursively(SENT_DIR, hours_to_live=HOURS_TO_LIVE)
    delete_old_files_recursively(LOG_DIR, hours_to_live=HOURS_TO_LIVE)

    stop_logging(LOG_FILE)

if __name__ == "__main__":
    main()
