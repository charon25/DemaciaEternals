import logging
import time

RASPBERRY_PI = True
if RASPBERRY_PI:
    from systemd import journal


LOG_FILE = ('/home/pi/eternals/' if RASPBERRY_PI else '') + 'log.txt'

class Logger:
    def __init__(self) -> None:
        self.unsaved_lines = []
        if RASPBERRY_PI:
            self.logger = logging.getLogger()
            self.logger.addHandler(journal.JournaldLogHandler())
            self.logger.setLevel(logging.INFO)

    def add_line(self, origin, line):
        if RASPBERRY_PI:
            if 'error' in origin.lower():
                self.logger.error(f'[{time.strftime("%Y/%m/%d %H:%M:%S")}] [{origin.upper()}] {line}')
            else:
                self.logger.info(f'[{time.strftime("%Y/%m/%d %H:%M:%S")}] [{origin.upper()}] {line}')
        self.unsaved_lines.append(f'[{time.strftime("%Y/%m/%d %H:%M:%S")}] [{origin.upper()}] {line}')

    def save(self):
        with open(LOG_FILE, 'a', encoding='utf-8') as fo:
            fo.write('\n'.join(self.unsaved_lines) + '\n')
        self.unsaved_lines = []
        self.add_line('log', 'Log written')
