import json
import platform
import psutil
import socket


system_network_count = 0
disk_partition_count = 0
system_ip_network_count = 0
i=1
# example dictionary that contains data like you want to have in json
# dic={'platform': platform.platform(), 'name': 'mkyong.com', 'messages': ['msg 1', 'msg 2', 'msg 3']}
#data = {'platform':platform.platform(),'attributes':[{'machine':platform.machine(),'processor':platform.processor(),'disk_partition':psutil.disk_partitions()}]}
# get json string from that dictionary
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



data={
	'Name ':platform.uname()[1],
	'Fully Qualified Domain Name ':socket.getfqdn(),
	'Operating System ':platform.platform(),
	'Processor ':platform.processor(),
	'Architecture ':platform.machine(),
	'No of CPU Cores ':psutil.cpu_count(logical=False),
	'Storage ':{	
				  'No of Partitions ':disk_partition_count,

				  'Disks':[],

		          'Total Memory ':psutil.virtual_memory()[0]
	}
}

i = 1
for disk_partition in disk_partitions:
	data["Storage "]['Disks'].append({"Disk"+str(i):disk_partition+psutil.disk_usage(disk_partition[1])})
	i=i+1
#data["Storage "]['Disks'].append({"f":var})
#data["Storage "]['Disks'].append({"q":var})
#data["Storage "]["No of Partitions "]["Disks"].append({"f":var})
#jsobj["a"]["b"]["e"].append({"f":var3, "g":var4, "h":var5})
json=json.dumps(data,sort_keys=True)
#data = json.dumps(data,sort_keys=True)
#print(json)
print('CPU Freq',psutil.cpu_freq())
#print(psutil.disk_usage(disk_partition[1]))
f=open('sysinfo1','w')
#print(json,file=f)
f.write(json)
f.close()