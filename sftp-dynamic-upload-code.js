/**
 * Nodo Code para Upload sFTP Dinámico
 *
 * INSTALACIÓN PREVIA EN SERVIDOR N8N:
 * npm install ssh2-sftp-client
 *
 * PROPÓSITO:
 * Reemplazar el nodo "sFTP - Upload llms.txt" del workflow para
 * soportar credenciales dinámicas desde Google Sheets sin necesidad
 * de crear 20+ credenciales en n8n.
 *
 * USO:
 * 1. En el workflow, eliminar el nodo "sFTP - Upload llms.txt"
 * 2. Agregar un nuevo nodo "Code"
 * 3. Copiar este código en el campo de JavaScript
 * 4. Conectar después del nodo "Construir Archivo llms.txt"
 */

const SftpClient = require('ssh2-sftp-client');
const sftp = new SftpClient();

// Configuración dinámica desde el flujo de datos
const config = {
  host: $json.sFTP_Host,
  port: 22, // Cambiar si usas puerto no estándar
  username: $json.sFTP_User,
  password: $json.sFTP_Pass,
  readyTimeout: 20000, // 20 segundos timeout
  retries: 3, // Reintentos en caso de fallo
  retry_factor: 2,
  retry_minTimeout: 2000
};

// Configuración de destino
const remotePath = '/public_html/llms.txt'; // Ajustar según tu estructura de servidor
const localContent = Buffer.from($json.llms_txt_content, 'utf-8');

// Logging para debugging
console.log(`📤 Iniciando upload sFTP a: ${config.host}${remotePath}`);
console.log(`📊 Tamaño del archivo: ${localContent.length} bytes`);

try {
  // Conectar al servidor sFTP
  await sftp.connect(config);
  console.log(`✅ Conectado a ${config.host}`);

  // Verificar que el directorio existe (opcional, crear si no existe)
  const remoteDir = remotePath.substring(0, remotePath.lastIndexOf('/'));
  const dirExists = await sftp.exists(remoteDir);

  if (!dirExists) {
    console.log(`📁 Creando directorio: ${remoteDir}`);
    await sftp.mkdir(remoteDir, true); // true = recursive
  }

  // Subir el archivo (sobrescribe si existe)
  await sftp.put(localContent, remotePath);
  console.log(`✅ Archivo subido exitosamente: ${remotePath}`);

  // Verificar que el archivo se subió correctamente
  const fileInfo = await sftp.stat(remotePath);
  console.log(`📋 Verificación - Tamaño en servidor: ${fileInfo.size} bytes`);

  // Cerrar conexión
  await sftp.end();
  console.log(`🔒 Conexión sFTP cerrada`);

  // Retornar datos para el siguiente nodo
  return {
    json: {
      ...$json,
      upload_status: 'SUCCESS',
      upload_timestamp: new Date().toISOString(),
      file_size_uploaded: fileInfo.size,
      remote_path: remotePath,
      sftp_host: config.host
    }
  };

} catch (error) {
  console.error(`❌ Error en upload sFTP:`, error);

  // Intentar cerrar conexión en caso de error
  try {
    await sftp.end();
  } catch (closeError) {
    console.error('Error al cerrar conexión:', closeError);
  }

  // Retornar error pero no detener el workflow
  return {
    json: {
      ...$json,
      upload_status: 'FAILED',
      error_message: error.message,
      error_code: error.code || 'UNKNOWN',
      upload_timestamp: new Date().toISOString(),
      sftp_host: config.host
    }
  };
}

/**
 * NOTAS DE CONFIGURACIÓN:
 *
 * 1. PUERTO PERSONALIZADO:
 *    Si tu servidor usa puerto diferente a 22:
 *    port: $json.sFTP_Port || 22
 *    (Agregar columna sFTP_Port en Google Sheets)
 *
 * 2. RUTA PERSONALIZADA:
 *    Si la estructura de directorios varía por sitio:
 *    remotePath: $json.sFTP_Path || '/public_html/llms.txt'
 *    (Agregar columna sFTP_Path en Google Sheets)
 *
 * 3. AUTENTICACIÓN CON LLAVE PRIVADA:
 *    Si prefieres SSH key en lugar de password:
 *    const config = {
 *      host: $json.sFTP_Host,
 *      port: 22,
 *      username: $json.sFTP_User,
 *      privateKey: $json.sFTP_PrivateKey, // Contenido de la llave privada
 *      passphrase: $json.sFTP_Passphrase || undefined
 *    };
 *
 * 4. BACKUP ANTES DE SOBRESCRIBIR:
 *    Para crear backup del archivo anterior:
 *    const backupPath = remotePath + '.backup.' + Date.now();
 *    const exists = await sftp.exists(remotePath);
 *    if (exists) {
 *      await sftp.rename(remotePath, backupPath);
 *    }
 *    await sftp.put(localContent, remotePath);
 *
 * 5. MÚLTIPLES ARCHIVOS:
 *    Si necesitas subir llms.txt a múltiples ubicaciones:
 *    const paths = ['/public_html/llms.txt', '/docs/llms.txt'];
 *    for (const path of paths) {
 *      await sftp.put(localContent, path);
 *    }
 *
 * TROUBLESHOOTING:
 *
 * Error: "Cannot find module 'ssh2-sftp-client'"
 * Solución: Ejecutar en el servidor n8n:
 *   docker exec -it n8n npm install ssh2-sftp-client
 *   (ajustar comando según tu instalación)
 *
 * Error: "connect ETIMEDOUT"
 * Solución: Verificar firewall, aumentar readyTimeout a 30000
 *
 * Error: "Permission denied"
 * Solución: Verificar que sFTP_User tenga permisos de escritura en el directorio
 *
 * Error: "No such file or directory"
 * Solución: Ajustar remotePath según la estructura real del servidor
 */
