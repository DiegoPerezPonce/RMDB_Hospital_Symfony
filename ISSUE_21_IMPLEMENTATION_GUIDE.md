# Guía de Implementación - Issue #21: Dynamic Nurse Listing

## Resumen del Issue
**Título:** Dynamic Nurse Listing #21  
**Descripción:** Reemplazar el array mock local de enfermeros con datos reales obtenidos del servidor. La lista debe actualizarse dinámicamente basándose en el estado de la base de datos.

## Criterios de Aceptación

### ✅ 1. Implementar getNurses() usando HttpClient.get() retornando un Observable

**Backend (Symfony) - ✅ COMPLETADO**
- Endpoint disponible: `GET http://localhost:8000/nurse/index`
- Retorna JSON con todos los enfermeros
- Incluye todos los campos: id, user, name, pw, title, specialty, description, location, availability, image

**Frontend (Angular) - IMPLEMENTACIÓN REQUERIDA:**

```typescript
// nurse.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Nurse } from '../models/nurse.model';

@Injectable({
  providedIn: 'root'
})
export class NurseService {
  private apiUrl = 'http://localhost:8000/nurse';

  constructor(private http: HttpClient) {}

  getNurses(): Observable<Nurse[]> {
    return this.http.get<Nurse[]>(`${this.apiUrl}/index`);
  }
}
```

### ✅ 2. Usar AsyncPipe o .subscribe() en el componente para renderizar datos en el DOM

**Opción A: Usando AsyncPipe (Recomendado)**

```typescript
// nurse-list.component.ts
import { Component, OnInit } from '@angular/core';
import { Observable } from 'rxjs';
import { NurseService } from '../services/nurse.service';
import { Nurse } from '../models/nurse.model';

@Component({
  selector: 'app-nurse-list',
  templateUrl: './nurse-list.component.html'
})
export class NurseListComponent implements OnInit {
  nurses$: Observable<Nurse[]>;

  constructor(private nurseService: NurseService) {}

  ngOnInit(): void {
    this.nurses$ = this.nurseService.getNurses();
  }
}
```

```html
<!-- nurse-list.component.html -->
<div *ngIf="nurses$ | async as nurses; else loading">
  <div *ngIf="nurses.length === 0; else nurseList">
    <p>No hay enfermeros en la base de datos.</p>
  </div>
  <ng-template #nurseList>
    <table>
      <tr *ngFor="let nurse of nurses">
        <td>{{ nurse.name }}</td>
        <td>{{ nurse.specialty }}</td>
        <td>{{ nurse.availability }}</td>
        <td>
          <img [src]="nurse.image" [alt]="nurse.name" />
        </td>
      </tr>
    </table>
  </ng-template>
</div>
<ng-template #loading>
  <p>Cargando enfermeros...</p>
</ng-template>
```

**Opción B: Usando .subscribe()**

```typescript
// nurse-list.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { Subscription } from 'rxjs';
import { NurseService } from '../services/nurse.service';
import { Nurse } from '../models/nurse.model';

@Component({
  selector: 'app-nurse-list',
  templateUrl: './nurse-list.component.html'
})
export class NurseListComponent implements OnInit, OnDestroy {
  nurses: Nurse[] = [];
  loading = true;
  error: string | null = null;
  private subscription: Subscription;

  constructor(private nurseService: NurseService) {}

  ngOnInit(): void {
    this.subscription = this.nurseService.getNurses().subscribe({
      next: (nurses) => {
        this.nurses = nurses;
        this.loading = false;
      },
      error: (error) => {
        this.error = 'Error al cargar enfermeros';
        this.loading = false;
        console.error('Error:', error);
      }
    });
  }

  ngOnDestroy(): void {
    if (this.subscription) {
      this.subscription.unsubscribe();
    }
  }
}
```

```html
<!-- nurse-list.component.html -->
<div *ngIf="loading">Cargando enfermeros...</div>
<div *ngIf="error">{{ error }}</div>
<div *ngIf="!loading && !error">
  <div *ngIf="nurses.length === 0">
    <p>No hay enfermeros en la base de datos.</p>
  </div>
  <div *ngIf="nurses.length > 0">
    <table>
      <tr *ngFor="let nurse of nurses">
        <td>{{ nurse.name }}</td>
        <td>{{ nurse.specialty }}</td>
        <td>{{ nurse.availability }}</td>
        <td>
          <img [src]="nurse.image" [alt]="nurse.name" />
        </td>
      </tr>
    </table>
  </div>
</div>
```

### ✅ 3. Asegurar que las imágenes de perfil se carguen correctamente desde las URLs proporcionadas por el Backend

**Backend (Symfony) - ✅ COMPLETADO**
- El campo `image` se retorna en el JSON con la ruta completa (ej: `/img/raymond.png`)

**Frontend (Angular) - IMPLEMENTACIÓN REQUERIDA:**

```typescript
// nurse.model.ts
export interface Nurse {
  id: number;
  user: string;
  name: string;
  pw?: string;
  title?: string;
  specialty?: string;
  description?: string;
  location?: string;
  availability?: string;
  image?: string;
}
```

```html
<!-- Manejo de imágenes con fallback -->
<img 
  [src]="getNurseImage(nurse.image)" 
  [alt]="nurse.name"
  (error)="handleImageError($event)"
  class="nurse-profile-image"
/>

<!-- O con ngIf para mostrar solo si existe imagen -->
<img 
  *ngIf="nurse.image"
  [src]="nurse.image" 
  [alt]="nurse.name"
  (error)="handleImageError($event)"
/>
```

```typescript
// nurse-list.component.ts
getNurseImage(imagePath: string | undefined): string {
  if (!imagePath) {
    return '/assets/images/default-nurse.png'; // Imagen por defecto
  }
  // Si la imagen viene con ruta relativa, construir URL completa
  if (imagePath.startsWith('/')) {
    return `http://localhost:8000${imagePath}`;
  }
  return imagePath;
}

handleImageError(event: Event): void {
  const img = event.target as HTMLImageElement;
  img.src = '/assets/images/default-nurse.png';
}
```

### ✅ 4. Manejar estados vacíos (mostrar mensaje si no hay enfermeros en la BD)

**Implementación en el Template:**

```html
<!-- Con AsyncPipe -->
<div *ngIf="nurses$ | async as nurses">
  <div *ngIf="nurses.length === 0" class="empty-state">
    <h3>No hay enfermeros disponibles</h3>
    <p>La base de datos no contiene ningún enfermero registrado.</p>
    <button (click)="refreshNurses()">Recargar</button>
  </div>
  <div *ngIf="nurses.length > 0">
    <!-- Lista de enfermeros -->
  </div>
</div>

<!-- Con .subscribe() -->
<div *ngIf="!loading && !error">
  <div *ngIf="nurses.length === 0" class="empty-state">
    <h3>No hay enfermeros disponibles</h3>
    <p>La base de datos no contiene ningún enfermero registrado.</p>
    <button (click)="refreshNurses()">Recargar</button>
  </div>
  <div *ngIf="nurses.length > 0">
    <!-- Lista de enfermeros -->
  </div>
</div>
```

**Manejo de errores de conexión:**

```typescript
// nurse-list.component.ts
ngOnInit(): void {
  this.loadNurses();
}

loadNurses(): void {
  this.loading = true;
  this.error = null;
  
  this.nurseService.getNurses().subscribe({
    next: (nurses) => {
      this.nurses = nurses;
      this.loading = false;
    },
    error: (error) => {
      this.loading = false;
      if (error.status === 0) {
        this.error = 'Error de conexión: No se pudo conectar al servidor';
      } else if (error.status === 500) {
        this.error = 'Error del servidor: Por favor, intente más tarde';
      } else {
        this.error = 'Error al cargar enfermeros';
      }
      console.error('Error:', error);
    }
  });
}

refreshNurses(): void {
  this.loadNurses();
}
```

## Verificación del Backend

### ✅ Endpoint Verificado
```bash
# Probar el endpoint
curl http://localhost:8000/nurse/index
```

**Respuesta esperada:**
```json
[
  {
    "id": 1,
    "user": "raymond",
    "name": "Raymond",
    "pw": "2006",
    "title": "RN",
    "specialty": "Pediatrics",
    "description": "Highly experienced pediatric nurse...",
    "location": "Carrer de la Marina, 23, 08005 Barcelona",
    "availability": "Available",
    "image": "/img/raymond.png"
  },
  ...
]
```

## Checklist de Implementación

- [x] Backend: Endpoint `/nurse/index` implementado y funcionando
- [x] Backend: Retorna todos los campos necesarios incluyendo `image`
- [ ] Frontend: Servicio `NurseService` con método `getNurses()` usando `HttpClient.get()`
- [ ] Frontend: Componente usa `AsyncPipe` o `.subscribe()` para obtener datos
- [ ] Frontend: Template renderiza datos dinámicamente desde el Observable
- [ ] Frontend: Imágenes de perfil se cargan correctamente desde URLs del backend
- [ ] Frontend: Manejo de estado vacío (mensaje cuando no hay enfermeros)
- [ ] Frontend: Manejo de errores de conexión y estados de carga
- [ ] Frontend: La lista se actualiza automáticamente cuando cambia la BD

## Notas Importantes

1. **CORS:** Ya está configurado en el backend para permitir `localhost:4200`
2. **URL del Backend:** Asegúrate de que coincida con la configuración de Angular
3. **Tipos TypeScript:** Define la interfaz `Nurse` para type safety
4. **Manejo de Errores:** Implementa manejo robusto de errores de red
5. **Performance:** Considera usar `AsyncPipe` para mejor manejo de memoria

## Próximos Pasos

1. Implementar el servicio `NurseService` con `getNurses()`
2. Actualizar el componente para usar datos del servidor
3. Implementar manejo de imágenes con fallback
4. Agregar estados vacíos y manejo de errores
5. Probar con diferentes estados de la base de datos
