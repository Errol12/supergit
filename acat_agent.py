import json
import platform
import psutil
import socket
import os
import subprocess
import re
import ipaddress
import sys
import netifaces as nif

system_network_count = 0
disk_partition_count = 0
system_ip_network_count = 0
i=1
# example dictionary that contains data like you want to have in json
# dic={'platform': platform.platform(), 'name': 'mkyong.com', 'messages': ['msg 1', 'msg 2', 'msg 3']}
#data = {'platform':platform.platform(),'attributes':[{'machine':platform.machine(),'processor':platform.processor(),'disk_partition':psutil.disk_partitions()}]}
# get json string from that dictionary


if len(sys.argv) < 2:
    print('You failed to provide Client unique key as input') 
    sys.exit(1)  # abort because of error


def mac_for_ip(ip):
    'Returns a list of MACs for interfaces that have given IP, returns None if not found'
    for i in nif.interfaces():
        addrs = nif.ifaddresses(i)
        try:
            if_mac = addrs[nif.AF_LINK][0]['addr']
            if_ip = addrs[nif.AF_INET][0]['addr']
        except IndexError: #ignore ifaces that dont have MAC or IP
            if_mac = if_ip = None
        if if_ip == ip:
            return if_mac
    return 'None'


disk_partitions = psutil.disk_partitions(all=True)
for disk_partition in disk_partitions:
	disk_partition_count = disk_partition_count + 1

system_networks = psutil.net_if_addrs()
for system_network in system_networks:
	system_network_count = system_network_count + 1

system_ip_networks = psutil.net_connections()
for system_ip_network in system_ip_networks:
	system_ip_network_count = system_ip_network_count + 1

#platform.platform()

print('Descriptions')
print('Name :',platform.uname()[1])
print('Fully Qualified Domain Name :',socket.getfqdn())
print('Operating System :',platform.platform())
print('Processor :',platform.processor())
print('Architecture :',platform.machine())
print('Total Memory :',psutil.virtual_memory()[0])
print('')
print('Storage :')
print('no of Partitions :',disk_partition_count)
#print('Partitions :',psutil.disk_partitions(all=True))
for disk_partition in disk_partitions:
	print('Disk ',i)
	print(disk_partition)
	print(psutil.disk_usage(disk_partition[1]))
	i=i+1

print('')
print('Network')

s =  socket.gethostbyname(socket.gethostname())

if platform.system() == "Windows":
        #processor = platform.processor()
        command = "wmic CPU get name"
        processor1 = subprocess.check_output(command, shell=True).strip()
        processor = str(processor1).split('\\n')
        processor = processor[1].replace("'", "")
elif platform.system() == "Darwin":
        os.environ['PATH'] = os.environ['PATH'] + os.pathsep + '/usr/sbin'
        command ="sysctl -n machdep.cpu.brand_string"
        processor = subprocess.check_output(command).strip()
elif platform.system() == "Linux":
        command = "cat /proc/cpuinfo"
        all_info = subprocess.check_output(command, shell=True).strip()
        for line in all_info.split("\n"):
            if "model name" in line:
                processor = re.sub( ".*model name.*:", "", line,1)

if platform.system() == "Windows":
	net = ipaddress.ip_network(s)
elif platform.system() == "Linux":
	v = s.decode('utf-8')
	net = ipaddress.ip_network(v)


#net = ipaddress.ip_network(s)
print(net)
print('is private:', net.is_private)
print('ipaddress:', net.broadcast_address)
print('compressed:', net.compressed)
print('with subnet mask:', net.with_netmask)
print('with hostmask:', net.with_hostmask)
print('num addresses:', net.num_addresses)
is_private = net.is_private
ipaddr = str(net.broadcast_address)
compressed = net.compressed
subnet = net.with_netmask
hostmask = net.with_hostmask

a = []



data={
	'UID':str(sys.argv[1]),
	'Name':platform.uname()[1],
	'Fully Qualified Domain Name':socket.getfqdn(),
	'Operating System':platform.platform(),
	'Processor':processor,
	'Platform':platform.system(),
	'Architecture':platform.machine(),
	'No of CPU Cores':psutil.cpu_count(logical=False),
	'System Memory':psutil.virtual_memory()[0],
	'Storage':{	
				  'No of Partitions':disk_partition_count

			},
	'Network':[

				   
				   
	]
}

i = 1
for disk_partition in disk_partitions:
	#data["Storage "]['Disks'].append({"Disk"+str(i):disk_partition+psutil.disk_usage(disk_partition[1])})
	i=i+1


network_names = []
for system_network in system_networks:
	print(system_networks)
	network_names.append(system_network)

i=1		
for nic, addrs in psutil.net_if_addrs().items():
	#data["Network_2 "].append({"Network 1":nic})
	print(nic)
	name = nic
	for addr in addrs:
	 if addr.family == socket.AF_INET:
	  if mac_for_ip(addr.address):
	  	macaddr = mac_for_ip(addr.address)
	  else:
	  	macaddr = 'NA'	
	  data["Network"].append({"Network"+str(i):nic,"Ipaddress":addr.address ,"Subaddress":addr.netmask,"Macaddress":macaddr})
	  i = i + 1




j=1
for disk_partition in disk_partitions:
	data['Storage'].update({"Disk"+str(j):[]})
	j = j + 1



j=1
for device in psutil.disk_partitions(all=True):
	#data["Storage_2"]['Disk'+str(j)].extend({"Disk"+str(j):disk_partition,"Disk2":psutil.disk_usage(disk_partition[1])})
	data["Storage"]['Disk'+str(j)].append({'Device': device.device,'Mountpoint':device.mountpoint,'fstype':device.fstype,'opts':device.opts,'total_memory':psutil.disk_usage(device.mountpoint)[0],'used_memory':psutil.disk_usage(device.mountpoint)[1],'free_memory':psutil.disk_usage(device.mountpoint)[2],'percent':psutil.disk_usage(device.mountpoint)[3]})
	j = j + 1



#data["Network_2 "].append({"Network":system_networks})
#data["Storage "]['Disks'].append({"f":var})
#data["Storage "]['Disks'].append({"q":var})
#data["Storage "]["No of Partitions "]["Disks"].append({"f":var})
#jsobj["a"]["b"]["e"].append({"f":var3, "g":var4, "h":var5})
json=json.dumps(data,sort_keys=True,indent=4)
#data = json.dumps(data,sort_keys=True)
#print(json)
#print('CPU Freq',psutil.cpu_freq())
print(processor)

for device in psutil.disk_partitions(all=True):
	print(device.device)

	#data["Storage "]['Disks']['Disk1'].extend({"Device":device.device}) 
		#print(dev)

#for name, stats in psutil.net_if_stats().items():
    #print(name, stats.speed)

#print(psutil.net_if_stats().items())



print(psutil.disk_usage('/')[0])

print("this is ",str(sys.argv[1]))
#print(psutil.disk_usage(disk_partition[1]))

f=open('acat_'+platform.system()+'.json','w')
#print(json,file=f)
f.write(json)
f.close()
print(json)









