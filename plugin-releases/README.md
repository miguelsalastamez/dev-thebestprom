# TBP Actividades — Historial de Releases

## ⚠️ VERSIÓN EN PRODUCCIÓN
**tbp-actividades-v8.8.0-PRODUCTION-NO-BORRAR.zip**
- Fecha: 2026-05-06
- Estado: ✅ ACTIVA EN SITIO OFICIAL thebestprom.com
- NO ELIMINAR

---

## Versiones

### v8.8.0 — Opción C+D: SQL Pagination + Cursor (PRODUCCIÓN)
- SQL COUNT + LIMIT/OFFSET: solo carga 25 pedidos por página (no 2000+)
- Cursor-based para filtros de metadata (máx 300 scan/request)
- Elimina el timeout de conexión en eventos con 2000+ pedidos

### v8.7.0 — Paginación clásica (retirada por timeout)
- Intentó paginar pero seguía cargando todos los pedidos en memoria
- Causa: `$all_rows` acumulaba 2000+ objetos WC antes de paginar

### v8.6.0 — Mobile Cards + Metadata Filters
- Vista mobile responsive (CSS cards via data-label)
- Panel de filtros dinámicos por metadata del asistente (Talla, Grupo, etc.)
- Click-to-filter: al hacer clic en un chip filtra la tabla

---

## Protocolo de Rollback
Para revertir a una versión anterior:
1. Descarga el ZIP correspondiente
2. En WordPress Admin → Plugins → Desactivar tbp-actividades
3. Subir e instalar el ZIP anterior
4. Activar el plugin
