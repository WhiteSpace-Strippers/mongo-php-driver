/**
 *	Copyright 2009-2012 10gen, Inc.
 *
 *	Licensed under the Apache License, Version 2.0 (the "License");
 *	you may not use this file except in compliance with the License.
 *	You may obtain a copy of the License at
 *
 *	http://www.apache.org/licenses/LICENSE-2.0
 *
 *	Unless required by applicable law or agreed to in writing, software
 *	distributed under the License is distributed on an "AS IS" BASIS,
 *	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *	See the License for the specific language governing permissions and
 *	limitations under the License.
 */
#ifndef __MCON_IO_H__
#define __MCON_IO_H__

int mongo_io_wait_with_timeout(int sock, int to, char **error_message);
int mongo_io_send(int sock, char *packet, int total, char **error_message);
int mongo_io_recv_header(int sock, char *reply_buffer, int size, char **error_message);
int mongo_io_recv_data(int sock, void *dest, int size, char **error_message);

#endif
