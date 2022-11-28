<template>
    <panel-item :field="field" >
        <template slot="value">
            <div>
                <p>Latitude: {{pos.lat}}</p>
                <p>Longitude: {{pos.lng}}</p>
                <div v-show="pos.lng != 0 || pos.lat != 0" class="gmap-size mt-4" ref="map"></div>
            </div>
        </template>
    </panel-item>
    
</template>

<script>

export default {
    data() {
        return {
            pos:{lat: 0, lng: 0},
            map: null,
            marker: null
        };
    },
    mounted(){
        this.pos = JSON.parse(this.field.value);

        if(this.pos.lng != 0 || this.pos.lat != 0){
            this.map = new google.maps.Map(this.$refs.map, {
                center: this.pos,
                zoom: 10
            });

            this.marker = new google.maps.Marker({
                position: this.pos,
                map: this.map,
                draggable: false
            });
        }
        
    },
    props: ['resource', 'resourceName', 'resourceId', 'field'],
}
</script>