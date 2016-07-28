#!/usr/bin/python
import csv
import datetime
import ftplib
import logging
import os
import shutil
import time
import traceback
import zipfile
import glob
import xlrd
import codecs
import sys
import __main__

import dbfread




def start_logging(log_fname, logging_level):
    # =========== setup logging ==============
    os.makedirs(os.path.split(log_fname)[0], exist_ok=True)
    logging.basicConfig(level=logging_level, format="%(asctime)s\t%(levelname)s\t%(message)s",
                        handlers=[logging.FileHandler(log_fname),
                                  logging.StreamHandler()])
    logging.info("Program started:\t%s", __main__.__file__)
    
def stop_logging(log_fname):
    # =========== stop logging ==============
    logging.info("Program finished, logfile:\t%s", log_fname)
    logging.shutdown()

def get_file_list_or_die(in_dirname, file_ext):
    in_fnames = glob.glob(os.path.join(in_dirname, "*." + file_ext))
    if not in_fnames:
        logging.info("No files found in:\t%s\t%s", in_dirname, file_ext)
        logging.info("Program finished")
        sys.exit(0)
    return in_fnames	
  

def list_dir_ext_recursive(in_dirname, file_ext):
    """Get list of files with given extension in directory recursively, ignore case."""
    fl = [os.path.join(r, f)
        for r, ds, fs in os.walk(in_dirname)
        for f in fs if f.lower().endswith(file_ext.lower())]
    logging.debug("list_dir_ext_recursive:\t%s", fl)
    return fl  
    
def list_dir_ext(in_dirname, file_ext):
    """Get list of files with given extension in directory non-recursively, ignore case."""
    fl = [os.path.join(in_dirname,f) for f in os.listdir(in_dirname) if f.endswith(file_ext.lower())]    
    logging.debug("list_dir_ext_recursive:\t%s", fl)
    return fl    
    
def get_file_timestamp(in_fname):
    file_modified = datetime.datetime.fromtimestamp(os.path.getmtime(in_fname))
    file_timestamp = file_modified.strftime("%Y%m%dT%H%M%S")
    return file_timestamp
    
def get_file_isotimestamp(in_fname):
    f_mod = datetime.datetime.fromtimestamp(os.path.getmtime(in_fname))
    return f_mod.strftime("%Y%m%dT%H%M%S")

def prumstav_ds100_csv2tsv(in_fname, sensor_id):
    """
    Convert XLS from datalogger Voltcraft DL-121TH to TSV
    """
    logging.info("\t\tConverting, id: \t%s\t%s", in_fname, sensor_id)
    tsv_header = ["datetime-gmt+1", "temperature", "humidity"]
    tsv_delimit = "\t"
    in_dirname = os.path.split(in_fname)[0]
    out_fname = os.path.join(in_dirname, get_file_timestamp(in_fname)+"_"+sensor_id+".tsv")  # CSV output file
    try:
        csv_reader = csv.reader(codecs.open(in_fname, 'r', encoding='utf-16-le'), delimiter=",", lineterminator="\n")
        csv_writer = csv.writer(open(out_fname, 'w+'), delimiter=tsv_delimit, lineterminator="\n")
        csv_writer.writerow(tsv_header)
        for i, in_row in enumerate(csv_reader):
            if i == 0:   #skip header
                continue
            #print (in_row)
            in_timedate=in_row[1]
            ti = datetime.datetime.strptime(in_timedate, "%d.%m.%Y %H:%M:%S")
            time_out = ti.strftime("%Y%m%dT%H%M%S")
            #print(time_out)
            temp = ""
            humidity = ""
            if len(in_row) == 4:        #fix error in csv format - points mixed with commas
                temp=in_row[2]
                humidity=in_row[3]
            if len(in_row) == 5:
                temp=in_row[2]+"."+in_row[3]
                humidity=in_row[4]
            #print(temp, humidity)
            tsv_data_row = [time_out, temp, humidity]
            csv_writer.writerow(tsv_data_row)
    except Exception as e:
            logging.error(traceback.format_exc())
    else:
        logging.debug("\tconverted:\t%s", in_fname)

def voltcraft_xls2tsv(in_fname, sensor_id):
    """
    Convert XLS from datalogger Voltcraft DL-121TH to TSV
    """
    logging.info("\t\tConverting, id: \t%s\t%s", in_fname, sensor_id)
    tsv_header = ["datetime-gmt+1", "temperature", "humidity"]
    tsv_delimit = "\t"
    book = xlrd.open_workbook(in_fname)
    sheet = book.sheet_by_index(0)
    in_dirname = os.path.split(in_fname)[0]
    out_fname = os.path.join(in_dirname, get_file_timestamp(in_fname)+"_"+sensor_id+".tsv")  # CSV output file
    try:
        with open(out_fname, 'a+') as tsv_file:
            csv_writer = csv.writer(tsv_file, delimiter=tsv_delimit, lineterminator="\n")
            csv_writer.writerow(tsv_header)
            for row_index in range(4,sheet.nrows):
                time_in=sheet.row_values(row_index)[0]
                ti = datetime.datetime.strptime(time_in, "%d-%m-%Y %H:%M:%S")
                time_out = ti.strftime("%Y%m%dT%H%M%S")
                tsv_data_row=[time_out]+sheet.row_values(row_index)[1:3]
                print(tsv_data_row)
                csv_writer.writerow(tsv_data_row)
        # ========== log XLS info ==============
        logging.debug("\tsheets=%s", book.nsheets)
        logging.debug("\tsheet names=%s", book.sheet_names())
    except Exception as e:
            logging.error(traceback.format_exc())
    else:
        logging.debug("\tconverted:\t%s", in_fname)


def merlin_hm8_xls2tsv(in_fname, sensor_id):
    """
    Convert XLS from datalogger Voltcraft DL-121TH to TSV. Change output filename to filetimestamp_fileid.tsv.
    """
    logging.info("\t\tConverting, id: \t%s\t%s", in_fname, sensor_id)
    tsv_header = ["datetime-gmt+1", "temperature", "humidity"]
    tsv_delimit = "\t"
    book = xlrd.open_workbook(in_fname)
    sheet = book.sheet_by_index(0)
    in_dirname = os.path.split(in_fname)[0]
    out_fname = os.path.join(in_dirname, get_file_timestamp(in_fname) +"_"+sensor_id+".tsv")  # CSV output file
    try:
        with open(out_fname, 'a+') as tsv_file:         #append to file if exists
            csv_writer = csv.writer(tsv_file, delimiter=tsv_delimit, lineterminator="\n")
            csv_writer.writerow(tsv_header)
            for row_index in range(5,sheet.nrows):
                date_in = sheet.row_values(row_index)[0]
                date = xldate_as_datetime(date_in, 0)
                date_out = datetime.datetime.strftime(date, "%Y%m%dT120000")
                tsv_data_row=[date_out,sheet.row_values(row_index)[2],sheet.row_values(row_index)[1]]
                #print(tsv_data_row)
                csv_writer.writerow(tsv_data_row)
        # ========== log XLS info ==============
        logging.debug("\ttsv:%s", out_fname)
        logging.debug("\tsheets=%s", book.nsheets)
        logging.debug("\tsheet names=%s", book.sheet_names())

    except Exception as e:
            logging.error(traceback.format_exc())
    else:
        logging.debug("\tconverted:\t%s", in_fname)


def convert_dbf_s3120(in_fname, out_fname):
    """
    Convert DBF into TSV
    :param out_fname:
    :param in_fname:
    """
    tsv_header = ["datetime-gmt+1", "temperature", "humidity"]
    tsv_delimit = "\t"
    dbf_file = dbfread.DBF(in_fname, encoding="Windows-1250")
    # ========== log DBF info ==============
    logging.info("\tconverting DBF:\t%s", in_fname)
    logging.debug("\trecords=%s", len(dbf_file))
    logging.debug("\tDBF format=%s", dbf_file.dbversion)
    logging.debug("\theader=%s", dbf_file.field_names)
    logging.debug("\tencoding=%s", dbf_file.encoding)
    # ========== convert DBF  ==============
    logging.info("\t\tWriting\t%s", out_fname)
    csv_writer = csv.writer(open(out_fname, "w+"), delimiter=tsv_delimit, lineterminator="\n")
    csv_writer.writerow(tsv_header)
    for record in dbf_file:
        rec_date = list(record.values())[0]
        rec_timestr = list(record.values())[1]
        rec_time = datetime.datetime.strptime(rec_timestr, "%H:%M:%S").time()
        rec_datetime = datetime.datetime.combine(rec_date, rec_time)
        rec_datetime_str = rec_datetime.strftime("%Y%m%dT%H%M%S")
        rec_temp = list(record.values())[3]
        rec_humi = list(record.values())[4]
        tsv_data_row = [rec_datetime_str, rec_temp, rec_humi]
        # print(out_row)
        # print(list(record.values())[1])
        csv_writer.writerow(tsv_data_row)


def dir2zip(zip_fname, dirname, file_ext=""):
    """
    Creates zip archive from folder (non-recursively), files can be filtered by extension
    :param file_ext:
    :param dirname:
    :param zip_fname:
    """
    logging.info("Creating Zip, source:\t%s\t%s\t%s", zip_fname, dirname, file_ext)
    zipped_count=0
    if os.listdir(dirname):
        with zipfile.ZipFile(zip_fname, "w", zipfile.ZIP_DEFLATED) as z:
            for fname in os.listdir(dirname):
                if fname.lower().endswith(file_ext.lower()):
                    z.write(os.path.join(dirname, fname), arcname=fname)
                    zipped_count += 1
                    logging.info("\tfile added:\t%s", fname)
    else:
        logging.info("\tDir empty:\t%s", dirname)
    return zipped_count

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
    fnames = list_dir_ext_recursive(dirname, file_ext)
    if fnames:
        with zipfile.ZipFile(zip_fname, "w", zipfile.ZIP_DEFLATED) as z:
            for fname in fnames:
                try:
                    z.write(fname, arcname=os.path.basename(fname))
                    os.remove(fname)
                except Exception as e:
                    logging.error(traceback.format_exc())
                else:
                    file_count += 1
                    logging.info("\tfile moved:\t%s", fname)
    else:
        logging.info("\tNo files:\t%s\t%s", dirname, file_ext)
    return file_count    
    
def delete_files_in_dir(dirname, file_ext=""):
    """
    Delete files in source_dir with extension
    :param dirname:
    :param dirname, file_ext:
    """
    logging.info("Deleting files in: \t%s \t with ext:\t%s", dirname, file_ext)
    deleted_count = 0
    fnames = glob.glob(os.path.join(dirname, "*." + file_ext))
    if not fnames:
        logging.info("\tnothing deleted - no files with ext:\t%s\t%s", dirname, file_ext)
        return deleted_count
    for fname in fnames:
        # print(fname)
        try:
            os.remove(fname)
        except Exception as e:
            logging.error(traceback.format_exc())
        else:
            deleted_count += 1
            logging.debug("\tdeleted:\t%s", fname)
    logging.info("\tcount:\t%s", deleted_count)
    return deleted_count

def agree_to_upload(fname):
    os.system(fname)			# open file to check it before upload
    agree_input = input("upload files to ftp? \n\tempty string = abort, \n\tanything else = upload\n")
    if not agree_input:
        print("upload aborted")
        logging.info("Upload aborted by user")
    return agree_input    
    
def upload2ftp_and_archive(in_dirname, file_ext, archive_dirname, ftp_host, ftp_login="anonymous", ftp_pw="anonymous",
                           remote_ftp_dir="/"):
    """
    Upload files with given extension to ftp and move them to archive
    """
    logging.info("Uploading to ftp:\t%s", ftp_host)
    uploaded_count = 0
    # ============ get DBF files list ==============
    fnames = list_dir_ext(in_dirname, file_ext)	#non-recursive 
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


def delete_old_files(dirname, hours_to_live):
    """
    Delete all old files from archive
    :param hours_to_live:
    :param dirname:
    """
    logging.info("Deleting old files in:\t%s", dirname)
    if not os.path.isdir(dirname):
        return 0
    deleted_count = 0
    file_list = os.listdir(dirname)
    for fname in file_list:
        fname = os.path.join(dirname, fname)
        file_modified = datetime.datetime.fromtimestamp(os.path.getmtime(fname))
        if datetime.datetime.now() - file_modified > datetime.timedelta(
                **{"hours": hours_to_live}):  # how many hours to store uploaded dbf files
            try:
                os.remove(fname)
            except Exception as e:
                logging.error(traceback.format_exc())
            else:
                deleted_count += 1
                logging.debug("\tdeleted:\t%s", fname)
    logging.info("\tdeleted_count:\t%s", deleted_count)
    return deleted_count
    
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
    fnames = list_dir_ext_recursive(dirname, file_ext)
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

def xldate_as_datetime(xldate, datemode):
    if datemode not in (0, 1):
        raise XLDateBadDatemode(datemode)
    if xldate == 0.00:
        return datetime.time(0, 0, 0)
    if xldate < 0.00:
        raise XLDateNegative(xldate)
    xldays = int(xldate)
    frac = xldate - xldays
    seconds = int(round(frac * 86400.0))
    assert 0 <= seconds <= 86400
    if seconds == 86400:
        seconds = 0
        xldays += 1

    if xldays == 0:
        # second = seconds % 60; minutes = seconds // 60
        minutes, second = divmod(seconds, 60)
        # minute = minutes % 60; hour    = minutes // 60
        hour, minute = divmod(minutes, 60)
        return datetime.time(hour, minute, second)

    if xldays < 61 and datemode == 0:
        raise XLDateAmbiguous(xldate)

    return (
        datetime.datetime.fromordinal(xldays + 693594 + 1462 * datemode)
        + datetime.timedelta(seconds=seconds)
        )

# ===================== main =========================================================

def main():
    pass


if __name__ == "__main__":
    main()
