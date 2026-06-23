"""
HaFI85 Digital Printing — Local Printer Agent
=============================================
Script ini dijalankan di PC toko yang terhubung ke jaringan LAN yang sama
dengan mesin-mesin print. Agent akan:
  1. Ping setiap printer untuk cek online/offline
  2. Coba baca status via SNMP (ink level, status printer)
  3. Kirim data ke web app via API setiap 30 detik

Cara pakai:
  1. Install Python 3.8+
  2. pip install requests
  3. Edit config.json (sesuaikan api_url dengan domain hosting)
  4. python hafi85_agent.py

Opsional (untuk SNMP monitoring):
  pip install pysnmp
"""

import json
import time
import subprocess
import sys
import logging
import platform
from datetime import datetime
from pathlib import Path

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    datefmt='%H:%M:%S'
)
log = logging.getLogger('HaFI85-Agent')

# ─── Load Config ────────────────────────────────────────────────────────────

CONFIG_PATH = Path(__file__).parent / 'config.json'

def load_config():
    with open(CONFIG_PATH, 'r') as f:
        return json.load(f)

# ─── Ping ───────────────────────────────────────────────────────────────────

def ping(ip, timeout=2):
    """Ping IP address. Returns True if host responds."""
    try:
        param = '-n' if platform.system().lower() == 'windows' else '-c'
        timeout_param = '-w' if platform.system().lower() == 'windows' else '-W'
        timeout_val = str(timeout * 1000) if platform.system().lower() == 'windows' else str(timeout)
        
        result = subprocess.run(
            ['ping', param, '1', timeout_param, timeout_val, ip],
            capture_output=True, text=True, timeout=timeout + 3,
            creationflags=subprocess.CREATE_NO_WINDOW if platform.system().lower() == 'windows' else 0
        )
        return result.returncode == 0
    except Exception:
        return False

# ─── SNMP (opsional) ───────────────────────────────────────────────────────

SNMP_AVAILABLE = False
try:
    from pysnmp.hlapi import (
        getCmd, nextCmd, SnmpEngine, CommunityData, 
        UdpTransportTarget, ContextData, ObjectType, ObjectIdentity
    )
    SNMP_AVAILABLE = True
    log.info("✓ pysnmp tersedia — SNMP monitoring aktif")
except ImportError:
    log.info("ℹ pysnmp tidak terinstall — hanya ping monitoring")
    log.info("  Install dengan: pip install pysnmp")

# Standard Printer MIB OIDs
OID_PRINTER_STATUS     = '1.3.6.1.2.1.25.3.5.1.1'        # hrPrinterStatus
OID_MARKER_SUPPLY_LVL  = '1.3.6.1.2.1.43.11.1.1.9'       # prtMarkerSuppliesLevel
OID_MARKER_SUPPLY_MAX  = '1.3.6.1.2.1.43.11.1.1.8'       # prtMarkerSuppliesMaxCapacity
OID_MARKER_COLOR       = '1.3.6.1.2.1.43.12.1.1.4'       # prtMarkerColorantValue
OID_DEVICE_DESCR       = '1.3.6.1.2.1.25.3.2.1.3'        # hrDeviceDescr
OID_DISPLAY_MSG        = '1.3.6.1.2.1.43.16.5.1.2.1.1'   # prtConsoleDisplayBufferText

# hrPrinterStatus values
PRINTER_STATUS_MAP = {
    1: 'idle',      # other
    2: 'idle',      # unknown  
    3: 'idle',      # idle
    4: 'printing',  # printing
    5: 'error',     # warmup
}

def snmp_get(ip, oid, community='public', timeout=2):
    """Get single SNMP value."""
    if not SNMP_AVAILABLE:
        return None
    try:
        iterator = getCmd(
            SnmpEngine(),
            CommunityData(community),
            UdpTransportTarget((ip, 161), timeout=timeout, retries=0),
            ContextData(),
            ObjectType(ObjectIdentity(oid))
        )
        errorIndication, errorStatus, errorIndex, varBinds = next(iterator)
        if errorIndication or errorStatus:
            return None
        for varBind in varBinds:
            return varBind[1]
    except Exception:
        return None

def snmp_walk(ip, oid, community='public', timeout=2):
    """Walk SNMP OID tree. Returns list of (oid, value) tuples."""
    if not SNMP_AVAILABLE:
        return []
    results = []
    try:
        for (errorIndication, errorStatus, errorIndex, varBinds) in nextCmd(
            SnmpEngine(),
            CommunityData(community),
            UdpTransportTarget((ip, 161), timeout=timeout, retries=0),
            ContextData(),
            ObjectType(ObjectIdentity(oid)),
            lexicographicMode=False
        ):
            if errorIndication or errorStatus:
                break
            for varBind in varBinds:
                results.append((str(varBind[0]), varBind[1]))
    except Exception:
        pass
    return results

def get_snmp_printer_status(ip):
    """Try to get printer status via SNMP. Returns dict or None."""
    if not SNMP_AVAILABLE:
        return None
    
    data = {}
    
    # 1. Printer status
    status_val = snmp_get(ip, OID_PRINTER_STATUS + '.1')
    if status_val is not None:
        try:
            status_int = int(status_val)
            data['status'] = PRINTER_STATUS_MAP.get(status_int, 'idle')
        except (ValueError, TypeError):
            pass
    
    # 2. Ink/toner levels
    levels = snmp_walk(ip, OID_MARKER_SUPPLY_LVL)
    maxes  = snmp_walk(ip, OID_MARKER_SUPPLY_MAX)
    colors = snmp_walk(ip, OID_MARKER_COLOR)
    
    if levels and maxes:
        ink_data = {}
        for i, (oid, val) in enumerate(levels):
            try:
                level = int(val)
                max_cap = int(maxes[i][1]) if i < len(maxes) else 100
                pct = int((level / max_cap) * 100) if max_cap > 0 else 0
                pct = max(0, min(100, pct))
                
                # Try to identify color
                color_name = ''
                if i < len(colors):
                    color_name = str(colors[i][1]).lower()
                
                if 'cyan' in color_name or i == 0:
                    ink_data['ink_c'] = pct
                elif 'magenta' in color_name or i == 1:
                    ink_data['ink_m'] = pct
                elif 'yellow' in color_name or i == 2:
                    ink_data['ink_y'] = pct
                elif 'black' in color_name or i == 3:
                    ink_data['ink_k'] = pct
            except (ValueError, TypeError, IndexError):
                continue
        
        data.update(ink_data)
    
    # 3. Display message (error/status text)
    msg = snmp_get(ip, OID_DISPLAY_MSG)
    if msg is not None:
        msg_str = str(msg).strip()
        if msg_str and msg_str.lower() not in ('ready', 'idle', ''):
            data['error_msg'] = msg_str
    
    return data if data else None

# ─── Check Single Printer ──────────────────────────────────────────────────

def check_printer(printer_config):
    """Check a single printer. Returns status dict."""
    name = printer_config['name']
    ip = printer_config['ip']
    
    result = {
        'name': name,
        'ip_address': ip,
        'status': 'offline',
    }
    
    # Step 1: Ping
    is_online = ping(ip)
    if not is_online:
        log.info(f"  ✕ {name} ({ip}) — OFFLINE")
        return result
    
    result['status'] = 'online'
    log.info(f"  ✓ {name} ({ip}) — ONLINE")
    
    # Step 2: Try SNMP
    if SNMP_AVAILABLE:
        snmp_data = get_snmp_printer_status(ip)
        if snmp_data:
            result.update(snmp_data)
            if 'ink_c' in snmp_data:
                log.info(f"    SNMP: ink C={snmp_data.get('ink_c','-')}% M={snmp_data.get('ink_m','-')}% Y={snmp_data.get('ink_y','-')}% K={snmp_data.get('ink_k','-')}%")
            if snmp_data.get('status'):
                log.info(f"    SNMP status: {snmp_data['status']}")
    
    return result

# ─── Send to API ───────────────────────────────────────────────────────────

def send_to_api(api_url, api_key, printer_data):
    """Send printer status data to web API."""
    try:
        import requests
        response = requests.post(
            api_url,
            json={'printers': printer_data},
            headers={
                'Content-Type': 'application/json',
                'X-API-KEY': api_key
            },
            timeout=15
        )
        if response.status_code == 200:
            result = response.json()
            log.info(f"  → API: {result.get('updated', 0)} printer(s) updated")
            return True
        else:
            log.warning(f"  → API error: HTTP {response.status_code}")
            return False
    except ImportError:
        log.error("Module 'requests' belum terinstall!")
        log.error("Jalankan: pip install requests")
        return False
    except Exception as e:
        log.warning(f"  → API error: {e}")
        return False

# ─── Main Loop ─────────────────────────────────────────────────────────────

def main():
    print("=" * 60)
    print("  HaFI85 Digital Printing — Local Printer Agent")
    print("=" * 60)
    print()
    
    config = load_config()
    api_url = config['api_url']
    api_key = config['api_key']
    interval = config.get('poll_interval_seconds', 30)
    printers = config['printers']
    
    log.info(f"API URL  : {api_url}")
    log.info(f"Interval : {interval} detik")
    log.info(f"Printers : {len(printers)} mesin LAN")
    print()
    
    # Check dependencies
    try:
        import requests
    except ImportError:
        log.error("=" * 50)
        log.error("Module 'requests' belum terinstall!")
        log.error("Jalankan: pip install requests")
        log.error("=" * 50)
        sys.exit(1)
    
    cycle = 0
    while True:
        cycle += 1
        log.info(f"── Cycle #{cycle} ──────────────────────────────")
        
        results = []
        for p in printers:
            data = check_printer(p)
            results.append(data)
        
        # Send to API
        if results:
            send_to_api(api_url, api_key, results)
        
        log.info(f"Menunggu {interval} detik...\n")
        
        try:
            time.sleep(interval)
        except KeyboardInterrupt:
            log.info("\nAgent dihentikan oleh user.")
            break

if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        print("\nAgent dihentikan.")
    except Exception as e:
        log.error(f"Error: {e}")
        input("Tekan Enter untuk keluar...")
